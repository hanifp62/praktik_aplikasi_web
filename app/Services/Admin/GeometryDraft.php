<?php

namespace App\Services\Admin;

use App\Models\Trail;
use App\Models\User;
use App\Support\GpxTrack;
use Illuminate\Support\Facades\Cache;

/**
 * Draf impor geometri satu jalur oleh satu kurator, di antara langkah-langkahnya
 * (PRD §65, §109): cari atau unggah, pratinjau, lalu simpan.
 *
 * Komponen Livewire dahulu menyimpan keadaan ini di dirinya sendiri, sehingga seluruh
 * kandidat OpenStreetMap beserta koordinatnya ikut terkirim bolak-balik pada setiap
 * klik. Di HTTP setiap langkah adalah permintaan tersendiri. Drafnya tinggal di cache,
 * bukan di sesi: sesi dibaca pada setiap halaman, dan ratusan kandidat berkoordinat tidak
 * boleh ikut termuat di halaman lain hanya karena kurator belum menyimpan.
 *
 * Kuncinya per pengguna dan per jalur, umurnya terbatas, dan draf dibuang begitu
 * geometri disimpan.
 *
 * Setiap sumber baru mengganti seluruh pratinjau beserta asalnya. Komponen lama hanya
 * mengosongkan sebagian, sehingga profil elevasi dari berkas GPX dapat ikut tersimpan
 * bersama geometri kandidat OSM, dan jejak audit mencatat asal yang bukan asal
 * geometrinya.
 */
class GeometryDraft
{
    private const UMUR_MENIT = 60;

    /** Kandidat yang ditampilkan sekaligus: menggulir dua ratus jalan setapak bukan cara memilih. */
    public const TAMPIL = 15;

    /**
     * @var array{kandidat: array<int, array<string, mixed>>, pratinjau: array<int, array{0: float, 1: float}>, panjangKm: float|null, profil: array<int, array{km: float, m: int}>, tanjakan: array{gain: int, loss: int}|null, asal: string|null, berkas: string|null}
     */
    private const KOSONG = [
        'kandidat' => [],
        'pratinjau' => [],
        'panjangKm' => null,
        'profil' => [],
        'tanjakan' => null,
        'asal' => null,
        'berkas' => null,
    ];

    private function __construct(private readonly string $kunci) {}

    public static function untuk(User $user, Trail $trail): self
    {
        return new self('impor-geometri:'.$user->getKey().':'.$trail->getKey());
    }

    /**
     * @return array{kandidat: array<int, array<string, mixed>>, pratinjau: array<int, array{0: float, 1: float}>, panjangKm: float|null, profil: array<int, array{km: float, m: int}>, tanjakan: array{gain: int, loss: int}|null, asal: string|null, berkas: string|null}
     */
    public function baca(): array
    {
        return array_replace(self::KOSONG, (array) Cache::get($this->kunci, []));
    }

    /**
     * Pencarian baru mengganti seluruh draf, termasuk pratinjau yang sedang terbuka.
     *
     * @param  array<int, array<string, mixed>>  $kandidat
     */
    public function mulaiPencarian(array $kandidat): void
    {
        $this->simpan(['kandidat' => $kandidat]);
    }

    /**
     * Jejak dari berkas GPX. Ketinggian ikut terbaca dari berkas yang sama; sebelumnya
     * dibuang tepat di titik ia masuk.
     *
     * @param  array{coordinates: array<int, array{0: float, 1: float}>, elevations: array<int, float|null>}  $jejak
     */
    public function dariBerkas(array $jejak, string $namaBerkas): void
    {
        $this->simpan([
            'kandidat' => $this->baca()['kandidat'],
            'pratinjau' => $jejak['coordinates'],
            'panjangKm' => round(GpxTrack::lengthKm($jejak['coordinates']), 2),
            'profil' => GpxTrack::profile($jejak['coordinates'], $jejak['elevations']),
            'tanjakan' => GpxTrack::gainLoss($jejak['elevations']),
            'berkas' => $namaBerkas,
        ]);
    }

    /**
     * Berkas yang gagal dibaca mengosongkan pratinjau sebelumnya, supaya yang tersimpan
     * nanti tidak pernah jejak dari berkas lain.
     */
    public function kosongkanPratinjau(): void
    {
        $this->simpan(['kandidat' => $this->baca()['kandidat']]);
    }

    /**
     * Kandidat dipilih manusia, tidak pernah otomatis. OpenStreetMap tidak membawa
     * ketinggian, jadi profil dan taksiran tanjakan dikosongkan, bukan diwarisi dari
     * berkas sebelumnya.
     */
    public function pilihKandidat(int $osmId): bool
    {
        $draf = $this->baca();
        $dipilih = collect($draf['kandidat'])->firstWhere('id', $osmId);

        if ($dipilih === null) {
            return false;
        }

        $this->simpan([
            'kandidat' => $draf['kandidat'],
            'pratinjau' => $dipilih['koordinat'],
            'panjangKm' => $dipilih['panjang_km'],
            'asal' => 'OpenStreetMap way '.$osmId.' ('.$dipilih['nama'].')',
        ]);

        return true;
    }

    public function buang(): void
    {
        Cache::forget($this->kunci);
    }

    /**
     * Kandidat yang ditampilkan: disaring nama, lalu dibatasi. Urutannya sudah dari yang
     * terpanjang (OpenStreetMapTrails::near()).
     *
     * @param  array<int, array<string, mixed>>  $kandidat
     * @return array<int, array<string, mixed>>
     */
    public static function saringKandidat(array $kandidat, string $saringan): array
    {
        if ($saringan !== '') {
            $kandidat = array_values(array_filter(
                $kandidat,
                fn (array $calon) => str_contains(mb_strtolower($calon['nama']), mb_strtolower($saringan))
            ));
        }

        return array_slice($kandidat, 0, self::TAMPIL);
    }

    /**
     * Selisih panjang jejak terhadap jarak yang sudah tercatat. Null bila salah satunya
     * belum ada, sehingga tampilan tidak mengarang perbandingan.
     */
    public static function selisihPersen(?float $panjangKm, ?float $tercatat): ?float
    {
        if ($panjangKm === null || $tercatat === null || $tercatat <= 0) {
            return null;
        }

        return round(abs($panjangKm - $tercatat) / $tercatat * 100, 1);
    }

    /**
     * @param  array<string, mixed>  $draf
     */
    private function simpan(array $draf): void
    {
        Cache::put($this->kunci, array_replace(self::KOSONG, $draf), now()->addMinutes(self::UMUR_MENIT));
    }
}
