<?php

namespace App\Services;

use App\Enums\ConsiderationOutcome;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Jalur yang disimpan seorang pendaki, dan pilihan bandingnya.
 *
 * DK-05: simpanan longgar, banding sempit. Batas simpanan (hiking.saved_trails.max)
 * hanya batas teknis supaya satu akun tidak menumpuk baris tanpa ujung. Yang menjaga
 * keputusan adalah batas banding (hiking.saved_trails.compare_max): lebih dari tiga kolom
 * tidak terbaca di layar 360 px, dan riset AllTrails menunjukkan beban memutuskan naik
 * ketika pilihan yang ditimbang bersamaan menumpuk.
 */
class ConsiderationService
{
    public function batasSimpanan(): int
    {
        return (int) config('hiking.saved_trails.max');
    }

    public function batasBanding(): int
    {
        return (int) config('hiking.saved_trails.compare_max');
    }

    /**
     * Menambah atau menghapus satu jalur dari simpanan pengguna.
     *
     * Dibungkus transaksi dengan kunci baris pengguna supaya periksa-lalu-tulisnya tidak
     * diselingi permintaan lain: tanpa ini, dua perangkat yang sama-sama membaca hitungan
     * di bawah batas bisa sama-sama lolos dan melewati batasnya. `lockForUpdate()` adalah
     * kunci sungguhan di PostgreSQL (basis data produksi) tetapi tanpa efek di SQLite
     * (basis data test), sehingga suite ini tidak membuktikan kuncinya mencegah balapan;
     * yang dibuktikan test hanyalah jalur idempotennya (lihat ConsiderationTest).
     */
    public function toggle(User $user, Trail $trail): ConsiderationOutcome
    {
        return DB::transaction(function () use ($user, $trail) {
            User::whereKey($user->id)->lockForUpdate()->first();

            $ada = TrailConsideration::where('user_id', $user->id)
                ->where('trail_id', $trail->id)
                ->first();

            if ($ada) {
                $ada->delete();

                return ConsiderationOutcome::DIHAPUS;
            }

            // Yang melewati batas ditolak, bukan menggeser yang tertua keluar. Menggeser
            // diam-diam menghilangkan jalur yang sedang ditimbang tepat ketika ia sedang
            // ditimbang.
            //
            // Dihitung dengan whereHas ke jalur yang masih active(), bukan seluruh baris
            // tabel penghubung: jalur arsip tidak pernah lagi terlihat di simpanan, jadi
            // menghitungnya di sini membuat batas dan tampilan tidak sepakat, dan simpanan
            // macet permanen tanpa jalan keluar yang terlihat.
            if ($this->jumlahSimpanan($user) >= $this->batasSimpanan()) {
                return ConsiderationOutcome::DITOLAK;
            }

            // createOrFirst menampung kiriman ganda pada pasangan yang sama: bila
            // permintaan kembar sudah menyisipkan baris ini lebih dulu, pelanggaran kunci
            // unik diperlakukan sebagai "sudah ada", bukan halaman 500.
            TrailConsideration::createOrFirst(['user_id' => $user->id, 'trail_id' => $trail->id]);

            return ConsiderationOutcome::DITAMBAHKAN;
        });
    }

    /**
     * Predikat "simpanan aktif": baris trail_considerations milik $user yang jalurnya
     * belum diarsip. BUKAN "masih terbit" -- jalur yang ditarik dari terbit
     * (is_published false) tapi belum diarsip tetap ikut, sama seperti yang tampil di
     * halaman Simpanan (R-022 hanya mengecualikan jalur arsip dari simpanan, bukan jalur
     * yang sekadar tidak terbit).
     *
     * Satu-satunya tempat predikat ini ditulis: jumlahSimpanan() dan simpanan() memakainya
     * supaya batas, hitungan kerangka, dan daftar tidak pernah berbeda pendapat.
     */
    private function kueriSimpananAktif(User $user): Builder
    {
        return TrailConsideration::where('user_id', $user->id)
            ->whereHas('trail', fn ($query) => $query->active());
    }

    /**
     * Jumlah jalur tersimpan yang masih aktif, sama dengan yang tampil di halaman Simpanan.
     *
     * Satu kueri hitung yang disaring lewat indeks unik trail_considerations (user_id,
     * trail_id) dan kunci utama trails, sehingga biayanya tetap berapa pun jumlah simpanan.
     * Dipakai batas simpanan dan hitungan Simpanan di kerangka (props bersama), jadi batas,
     * hitungan, dan daftar tidak pernah berbeda pendapat tentang jalur arsip.
     */
    public function jumlahSimpanan(User $user): int
    {
        return $this->kueriSimpananAktif($user)->count();
    }

    /**
     * Catatan simpanan aktif untuk sekumpulan jalur, berkunci id jalur (nilai null bila jalur
     * tersimpan tanpa catatan). Jalur yang tidak tersimpan tidak punya kunci.
     *
     * Dipakai kartu Jelajah untuk "tersimpan?" dan "bercatatan?" pada satu halaman hasil saja,
     * lewat predikat yang sama dengan hitungan dan daftar Simpanan (T4-M6 tinjauan akhir W3),
     * bukan membaca seluruh baris trail_considerations pembaca.
     *
     * @param  iterable<int, int>  $trailIds
     * @return Collection<int, string|null>
     */
    public function catatanUntuk(User $user, iterable $trailIds): Collection
    {
        return $this->kueriSimpananAktif($user)
            ->whereIn('trail_id', collect($trailIds)->values())
            ->pluck('note', 'trail_id');
    }

    /**
     * Menghapus satu jalur dari simpanan, tanpa syarat.
     *
     * Bukan toggle(): "Hapus" adalah tombol penghapusan, bukan sakelar. Kalau dipakai
     * toggle() dan tombolnya terpencet dua kali, panggilan kedua menemukan barisnya sudah
     * tidak ada lalu MENAMBAHKANNYA KEMBALI. delete() pada baris yang sudah tidak ada
     * tidak melakukan apa-apa, jadi mengulang aksi ini tidak mengubah apa pun. Yang dihapus
     * selalu baris milik $user, sehingga id jalur dari klien tidak dapat menyentuh simpanan
     * orang lain.
     *
     * Mengembalikan jumlah baris yang terhapus (0 atau 1), supaya pemanggil hanya menawarkan
     * Urungkan untuk penghapusan yang benar-benar terjadi (m4 tinjauan akhir W3).
     */
    public function remove(User $user, int $trailId): int
    {
        return TrailConsideration::where('user_id', $user->id)
            ->where('trail_id', $trailId)
            ->delete();
    }

    /**
     * Menyimpan satu jalur secara idempoten, dengan catatan pribadi bila ada (spec v3 bagian
     * 6.2 dan 9.1). Dipakai tombol simpan kartu dan bar aksi, dan oleh Urungkan sesudah
     * "Hapus dari simpanan" untuk mengembalikan jalurnya beserta catatannya.
     *
     * Berbeda dengan toggle(), kiriman ulang atau halaman yang basi tidak pernah membalik
     * simpanan menjadi terhapus: jalur yang sudah tersimpan tetap tersimpan dan catatannya
     * tidak ditimpa. Baris yang sudah ada mengembalikan ConsiderationOutcome::SUDAH_ADA,
     * berbeda dari DITAMBAHKAN, supaya pemanggil tidak menawarkan Urungkan-sebagai-hapus
     * untuk baris yang tidak dibuat panggilan ini (tinjauan Tugas 4 temuan 2; ruling
     * W3-4b). Batas simpanan dijaga dengan kunci yang sama dengan toggle(). Catatan
     * ditulis lewat simpanCatatan() setelah barisnya dipastikan milik $user.
     */
    public function simpan(User $user, Trail $trail, ?string $catatan = null): ConsiderationOutcome
    {
        return DB::transaction(function () use ($user, $trail, $catatan) {
            User::whereKey($user->id)->lockForUpdate()->first();

            if (TrailConsideration::where('user_id', $user->id)->where('trail_id', $trail->id)->exists()) {
                return ConsiderationOutcome::SUDAH_ADA;
            }

            if ($this->jumlahSimpanan($user) >= $this->batasSimpanan()) {
                return ConsiderationOutcome::DITOLAK;
            }

            $baris = TrailConsideration::createOrFirst(['user_id' => $user->id, 'trail_id' => $trail->id]);
            $this->simpanCatatan($baris, $catatan);

            return ConsiderationOutcome::DITAMBAHKAN;
        });
    }

    /**
     * Baris simpanan milik $user untuk satu jalur, atau 404.
     *
     * Dicari di dalam simpanan pengguna yang masuk, bukan lewat id baris dari klien:
     * baris pendaki lain dan jalur yang belum disimpan sama-sama tidak ditemukan, jadi
     * jawabannya tidak membocorkan simpanan siapa pun (PRD §77).
     */
    public function simpananMilik(User $user, int $trailId): TrailConsideration
    {
        return TrailConsideration::query()
            ->whereBelongsTo($user)
            ->where('trail_id', $trailId)
            ->with('trail:id,name')
            ->firstOrFail();
    }

    /**
     * Menulis catatan pribadi (FR-22) lalu mengembalikan isi yang tersimpan.
     *
     * Kosong atau hanya spasi berarti menghapus: kolomnya kembali null, bukan string
     * kosong yang terbaca sebagai catatan. `note` sengaja tidak fillable, jadi ditulis
     * lewat forceFill setelah pemanggil memastikan barisnya milik penulisnya
     * (simpananMilik() dan TrailConsiderationPolicy).
     */
    public function simpanCatatan(TrailConsideration $simpanan, ?string $catatan): ?string
    {
        $bersih = trim((string) $catatan);

        $simpanan->forceFill(['note' => $bersih === '' ? null : $bersih])->save();

        return $simpanan->note;
    }

    /**
     * Jalur tersimpan (masih aktif), untuk pemeriksaan "sudah disimpan?" dan baki.
     *
     * @return Collection<int, Trail>
     */
    public function forUser(User $user): Collection
    {
        return Trail::query()
            ->active()
            ->whereIn('id', TrailConsideration::where('user_id', $user->id)->pluck('trail_id'))
            ->with('mountain')
            ->get();
    }

    /**
     * Baris simpanan pengguna beserta jalurnya, terbaru dulu. Jalur arsip tidak ikut.
     *
     * Urutan kedua (id) memutus seri ketika beberapa jalur disimpan dalam detik yang
     * sama, sehingga "tiga simpanan terbaru" selalu tiga jalur yang sama.
     *
     * @return Collection<int, TrailConsideration>
     */
    public function simpanan(User $user): Collection
    {
        return $this->kueriSimpananAktif($user)
            ->with('trail.mountain')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Pilihan banding bawaan: simpanan terbaru yang masih terbit, sebanyak batas banding.
     * Jalur yang tidak terbit tidak dibandingkan (R-022), meski masih tersimpan.
     *
     * Koleksi Eloquent, bukan koleksi biasa: tabel banding meneruskannya ke pembacaan
     * status resmi yang menuntut koleksi model.
     *
     * @param  Collection<int, TrailConsideration>  $simpanan  hasil simpanan()
     * @return EloquentCollection<int, Trail>
     */
    public function pilihanBawaan(Collection $simpanan): EloquentCollection
    {
        return new EloquentCollection($simpanan
            ->map(fn (TrailConsideration $baris) => $baris->trail)
            ->filter(fn (Trail $jalur) => $jalur->is_published)
            ->take($this->batasBanding())
            ->values()
            ->all());
    }
}
