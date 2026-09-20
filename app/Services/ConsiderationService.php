<?php

namespace App\Services;

use App\Enums\ConsiderationOutcome;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Jalur yang sedang ditimbang.
 *
 * Batasnya lima dan keras. Lebih dari lima kolom tidak terbaca pada lebar 400px (§88),
 * dan riset AllTrails menunjukkan beban keputusan justru naik ketika pilihan menumpuk:
 * daftar panjang menunda keputusan alih-alih memperbaikinya.
 */
class ConsiderationService
{
    public const BATAS = 5;

    /**
     * Menambah atau menghapus satu jalur dari daftar pertimbangan pengguna.
     *
     * Dibungkus transaksi dengan kunci baris pengguna supaya periksa-lalu-tulisnya tidak
     * diselingi permintaan lain: tanpa ini, dua perangkat yang sama-sama membaca hitungan
     * di bawah batas bisa sama-sama lolos dan membuat batas lima menjadi enam -- persis
     * skenario lintas perangkat yang jadi alasan timbangan ini hidup di basis data, bukan
     * di sesi (lihat migrasinya). `lockForUpdate()` adalah kunci sungguhan di PostgreSQL
     * (basis data produksi) tetapi tanpa efek di SQLite (basis data test), sehingga suite
     * ini tidak -- dan tidak bisa -- membuktikan kuncinya benar-benar mencegah balapan;
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

            // Yang keenam ditolak, bukan menggeser yang tertua keluar. Menggeser diam-diam
            // menghilangkan jalur yang sedang ditimbang tepat ketika ia sedang ditimbang.
            //
            // Dihitung dengan whereHas ke jalur yang masih active(), bukan seluruh baris
            // tabel penghubung mentah: jalur yang sudah diarsipkan tidak pernah lagi
            // terlihat forUser() (lihat method itu), jadi menghitungnya di sini membuat
            // batas dan tampilan tidak sepakat -- pendaki yang lima jalurnya berisi dua
            // arsip melihat "3/5" tetapi tetap ditolak menambah yang keenam, timbangan
            // yang macet permanen tanpa jalan keluar yang terlihat. whereHas dipilih atas
            // forUser()->count() karena yang terakhir memuat model Trail lengkap beserta
            // relasi mountain-nya hanya untuk dibuang jadi sebuah integer.
            $jumlahAktif = TrailConsideration::where('user_id', $user->id)
                ->whereHas('trail', fn ($query) => $query->active())
                ->count();

            if ($jumlahAktif >= self::BATAS) {
                return ConsiderationOutcome::DITOLAK;
            }

            // createOrFirst menampung kiriman ganda pada pasangan yang sama: bila
            // permintaan kembar sudah menyisipkan baris ini sepersekian detik lebih dulu,
            // pelanggaran kunci unik ditangkap dan diperlakukan sebagai "sudah ada",
            // bukan dilemparkan ke pendaki sebagai halaman 500.
            TrailConsideration::createOrFirst(['user_id' => $user->id, 'trail_id' => $trail->id]);

            return ConsiderationOutcome::DITAMBAHKAN;
        });
    }

    /**
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
}
