<?php

namespace App\Services\Trails;

use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\TripType;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Pencarian dan saringan Jelajah (spec v3 bagian 6.2, FR-27).
 *
 * Dua tahap dengan satu sumber aturan. Tahap kueri menyaring yang dapat disaring basis data
 * dengan murah (terbit, kata kunci, wilayah, dan saringan lama dari URL yang pernah
 * dibagikan). Tahap saringan menilai setiap kandidat di memori terhadap atribut yang sudah
 * dihitung server (kecocokan, jenis trip, tuntutan teknis, status resmi, tanjakan), karena
 * kecocokan dan status efektif tidak dapat disaring SQL dan hitungan setiap pilihan saringan
 * butuh kandidat yang sama. Aturan yang dijaga:
 *
 * - hanya jalur terbit yang masuk hasil (R-022); pencarian nama gunung dikurung supaya OR-nya
 *   tidak meloloskan draf;
 * - jalur yang dikenal tetapi belum berdata tampil TERPISAH, tidak dihitung (R-008);
 * - nilai yang tidak diketahui tidak pernah lolos saringan rendah (R-006, R-007): tuntutan
 *   teknis yang belum diketahui bukan "rendah", tanjakan kosong tidak masuk rentang apa pun;
 * - status Tutup disembunyikan secara bawaan dan disebut sebagai jalur yang disembunyikan
 *   saringan, tidak dibuang diam-diam.
 *
 * Kalimat seperti "gunung dekat Bogor untuk pemula" dibaca menjadi tafsiran yang dapat
 * dilepas satu per satu (FR-27): wilayah dan tuntutan teknis rendah. Saringan eksplisit dari
 * URL selalu menang atas tafsiran; kalimat yang tidak dikenali dicari sebagai kata kunci
 * biasa dan pembaca diberi tahu.
 */
final class TrailSearch
{
    public const PER_HALAMAN = 12;

    public const MAKS_MENUNGGU = 8;

    /** Kunci tafsiran kalimat yang dapat dilepas lewat ?abaikan=. */
    public const TAFSIRAN = ['wilayah', 'teknis'];

    /** Urutan saringan; jalur yang tersembunyi dicatat pada saringan pertama yang menolaknya. */
    public const SARINGAN = ['status', 'kecocokan', 'teknis', 'jenis', 'naik'];

    /** Jenis trip yang dapat disaring Jelajah (prototype 02 dan 15). */
    public const JENIS = [TripType::TEKTOK, TripType::CAMPING];

    /** Status bawaan: Tutup tidak ikut sampai pembaca memintanya. */
    public const STATUS_BAWAAN = ['OPEN', 'RESTRICTED', 'UNKNOWN'];

    /**
     * @param  array{search: string, abaikan: array<int, string>, technical: string, region: string, distance: float|null, duration: int|null, kecocokan: array<int, string>|null, jenis: array<int, string>|null, teknisRendah: bool, status: array<int, string>, naikDari: int|null, naikSampai: int|null, tampilkanSemua: bool}  $filter
     */
    public function __construct(private array $filter) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function dariInput(array $input): self
    {
        $angka = static fn (mixed $v): ?float => is_numeric($v) ? (float) $v : null;
        $meter = static fn (mixed $v): ?int => is_numeric($v) && (int) $v >= 0 ? (int) $v : null;
        $daftar = static fn (mixed $v, array $boleh): array => is_string($v)
            ? array_values(array_intersect($boleh, array_map('trim', explode(',', $v))))
            : [];

        return new self([
            'search' => trim((string) ($input['search'] ?? '')),
            'abaikan' => $daftar($input['abaikan'] ?? null, self::TAFSIRAN),
            'technical' => (string) ($input['technical'] ?? ''),
            'region' => trim((string) ($input['region'] ?? '')),
            'distance' => $angka($input['distance'] ?? null),
            'duration' => $angka($input['duration'] ?? null) === null ? null : (int) $angka($input['duration']),
            // Tanpa parameter berarti tanpa saringan; parameter kosong berarti pembaca melepas
            // semua pilihannya, jadi tidak ada yang lolos (sama dengan status).
            'kecocokan' => array_key_exists('kecocokan', $input) ? $daftar($input['kecocokan'], array_column(RouteFitLabel::cases(), 'value')) : null,
            'jenis' => array_key_exists('jenis', $input) ? $daftar($input['jenis'], array_map(fn (TripType $t) => $t->value, self::JENIS)) : null,
            'teknisRendah' => ($input['teknisRendah'] ?? null) === '1',
            'status' => array_key_exists('status', $input)
                ? $daftar($input['status'], array_column(OfficialStatusValue::cases(), 'value'))
                : self::STATUS_BAWAAN,
            'naikDari' => $meter($input['naikDari'] ?? null),
            'naikSampai' => $meter($input['naikSampai'] ?? null),
            'tampilkanSemua' => ($input['tampilkanSemua'] ?? null) === '1',
        ]);
    }

    /**
     * Saringan yang sedang berlaku, untuk dikembalikan ke isian halaman.
     *
     * @return array<string, mixed>
     */
    public function filter(): array
    {
        return $this->filter;
    }

    /**
     * Tafsiran kalimat (FR-27): chip yang dapat dilepas, kunci yang sudah dilepas, dan catatan
     * bila kalimatnya tidak dikenali seluruhnya.
     *
     * @return array{chip: array<int, array{kunci: string, label: string}>, dilepas: array<int, string>, catatan: string|null}
     */
    public function tafsiran(): array
    {
        $bacaan = $this->bacaan();

        if ($bacaan === null) {
            return [
                'chip' => [],
                'dilepas' => [],
                'catatan' => $this->kalimatPemula() ? 'Kalimat Anda tidak dikenali seluruhnya, jadi dicari sebagai kata kunci.' : null,
            ];
        }

        // Chip hanya untuk tafsiran yang benar-benar berlaku: saringan eksplisit dari URL
        // menang, dan tafsiran yang kalah tidak boleh tampil seolah menyaring.
        $chip = [];

        if ($bacaan['wilayah'] !== null && $this->filter['region'] === '' && ! in_array('wilayah', $this->filter['abaikan'], true)) {
            $chip[] = ['kunci' => 'wilayah', 'label' => 'Wilayah: '.$bacaan['wilayah'].' ('.$bacaan['kota'].')'];
        }

        if ($this->teknisDariTafsiran()) {
            $chip[] = ['kunci' => 'teknis', 'label' => 'Tuntutan teknis: rendah'];
        }

        return ['chip' => $chip, 'dilepas' => $this->filter['abaikan'], 'catatan' => null];
    }

    /**
     * Kandidat hasil: jalur terbit yang lolos tahap kueri, urut nama. Diambil satu lebih dari
     * batas supaya pemanggil tahu hasilnya terpotong.
     *
     * @return Collection<int, Trail>
     */
    public function kandidat(int $batas): Collection
    {
        return $this->terapkan(Trail::query()->withPublishabilityData()->published())
            ->limit($batas + 1)
            ->get();
    }

    /**
     * Jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak berwenang.
     *
     * @return Collection<int, Trail>
     */
    public function menunggu(): Collection
    {
        return $this->terapkan(Trail::query()->active()->where('is_published', false))
            ->limit(self::MAKS_MENUNGGU)
            ->get();
    }

    /**
     * Saringan pertama yang menolak satu kandidat, atau null bila lolos semuanya. Atributnya
     * dihitung pemanggil (HalamanJelajah::atribut) dan dikirim juga ke klien, supaya hitungan
     * "Tampilkan N jalur" di lembar saringan memakai atribut yang sama.
     *
     * @param  array{kecocokan: string|null, jenis: array<int, string>, teknisRendah: bool, status: string, naik: int|null}  $atribut
     * @param  bool  $dinilai  kecocokan hanya menyaring bila pembacanya dinilai
     * @param  string|null  $abaikan  satu saringan yang dilewati, untuk menghitung pilihannya sendiri
     */
    public function gagalPada(array $atribut, bool $dinilai, ?string $abaikan = null): ?string
    {
        $f = $this->filter;
        $teknisRendah = $f['teknisRendah'] || $this->teknisDariTafsiran();

        $uji = [
            'status' => fn () => in_array($atribut['status'], $f['status'], true),
            'kecocokan' => fn () => ! $dinilai || $f['kecocokan'] === null || in_array($atribut['kecocokan'], $f['kecocokan'], true),
            'teknis' => fn () => ! $teknisRendah || $atribut['teknisRendah'],
            'jenis' => fn () => $f['jenis'] === null || array_intersect($f['jenis'], $atribut['jenis']) !== [],
            'naik' => fn () => ($f['naikDari'] === null && $f['naikSampai'] === null)
                || ($atribut['naik'] !== null
                    && ($f['naikDari'] === null || $atribut['naik'] >= $f['naikDari'])
                    && ($f['naikSampai'] === null || $atribut['naik'] <= $f['naikSampai'])),
        ];

        foreach (self::SARINGAN as $kunci) {
            if ($kunci !== $abaikan && ! $uji[$kunci]()) {
                return $kunci;
            }
        }

        return null;
    }

    /**
     * Tuntutan teknis rendah dari tafsiran kalimat yang belum dilepas; saringan teknis
     * eksplisit dari URL menang atasnya.
     */
    public function teknisDariTafsiran(): bool
    {
        $bacaan = $this->bacaan();

        return $bacaan !== null
            && $bacaan['teknis']
            && $this->filter['technical'] === ''
            && ! in_array('teknis', $this->filter['abaikan'], true);
    }

    public function tampilkanSemua(): bool
    {
        return $this->filter['tampilkanSemua'];
    }

    /**
     * @param  Builder<Trail>  $query
     * @return Builder<Trail>
     */
    private function terapkan(Builder $query): Builder
    {
        $bacaan = $this->bacaan();
        $search = $bacaan === null ? $this->filter['search'] : '';
        $region = $this->filter['region'] !== ''
            ? $this->filter['region']
            : (($bacaan['wilayah'] ?? null) !== null && ! in_array('wilayah', $this->filter['abaikan'], true) ? $bacaan['wilayah'] : '');
        $technical = $this->filter['technical'];
        $distance = $this->filter['distance'];
        $duration = $this->filter['duration'];

        return $query
            ->with('mountain')
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $group) use ($search) {
                    self::cocokKataKunci($group, 'name', $search)
                        ->orWhereHas('mountain', fn (Builder $m) => self::cocokKataKunci($m, 'name', $search));
                });
            })
            ->when($technical !== '', fn (Builder $q) => $q->where('technical_demand', $technical))
            ->when($region !== '', function (Builder $q) use ($region) {
                $q->whereHas('mountain', fn (Builder $m) => $m->where('region', $region)->orWhere('province', $region));
            })
            ->when($distance !== null, fn (Builder $q) => $q->where('distance_km', '<=', (float) $distance))
            ->when($duration !== null, fn (Builder $q) => $q->where('estimated_duration_minutes', '<=', (int) $duration * 60))
            // id memutus seri nama, supaya urutan dan paginasi sama di setiap kunjungan dan di
            // setiap mesin basis data (m1 tinjauan akhir W3).
            ->orderBy('name')
            ->orderBy('id');
    }

    /**
     * Kata kunci yang tidak peka huruf besar di SQLite dan PostgreSQL, dengan % dan _ dari
     * pembaca dicari apa adanya (I2 tinjauan akhir W3, R-024).
     *
     * `LIKE` tidak peka huruf besar di SQLite untuk ASCII tetapi peka di PostgreSQL, jadi
     * pembaca yang mengetik "rinjani" tidak menemukan "Rinjani" di Supabase walau suite SQLite
     * hijau. Bukan `whereLike()`: SQLite tidak punya karakter escape bawaan, sehingga % dan _
     * yang di-escape hanya benar di PostgreSQL. Satu ekspresi yang sama untuk kedua mesin:
     * kolom dan pola sama-sama huruf kecil, dengan ESCAPE yang ditulis eksplisit. Karakter
     * escape-nya "!", bukan garis miring terbalik, supaya literal SQL-nya terbaca sama oleh
     * setiap pengurai (PDO, SQLite, PostgreSQL dengan standard_conforming_strings apa pun).
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private static function cocokKataKunci(Builder $query, string $kolom, string $kataKunci): Builder
    {
        $pola = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($kataKunci)).'%';

        return $query->whereRaw('lower('.$query->qualifyColumn($kolom).") like ? escape '!'", [$pola]);
    }

    private function kalimatPemula(): bool
    {
        return $this->filter['search'] !== ''
            && preg_match('/(?:\b(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b|\b(?:pemula|beginner|novice)\b)/i', $this->filter['search']) === 1;
    }

    /**
     * Kalimat sederhana seperti "dekat Malang untuk pemula" dibaca menjadi wilayah dan tuntutan
     * teknis rendah. Null bila kalimatnya tidak dikenali; pencariannya lalu memakai kata kunci.
     *
     * @return array{wilayah: string|null, kota: string, teknis: bool}|null
     */
    private function bacaan(): ?array
    {
        $search = $this->filter['search'];

        if (! $this->kalimatPemula()) {
            return null;
        }

        $lokasi = null;

        if (preg_match('/\b(?:dekat|sekitar|di|ke|dari)\s+([a-z0-9\s-]+?)(?=\s+(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b|\s*$)/i', $search, $m)) {
            $lokasi = trim($m[1]);
        } elseif (preg_match('/\b([a-z0-9\s-]+?)\s+(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b/i', $search, $m)) {
            $lokasi = trim($m[1]);
        }

        $kota = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $lokasi)));

        $alias = match ($kota) {
            'malang', 'surabaya' => 'Jawa Timur',
            'bandung', 'bogor' => 'Jawa Barat',
            'semarang' => 'Jawa Tengah',
            default => null,
        };

        if ($alias === null) {
            return null;
        }

        return ['wilayah' => $alias, 'kota' => ucwords($kota), 'teknis' => true];
    }
}
