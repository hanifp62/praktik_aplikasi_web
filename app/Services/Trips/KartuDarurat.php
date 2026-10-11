<?php

namespace App\Services\Trips;

use App\Enums\AuthorityType;
use App\Models\Authority;
use App\Models\Trail;
use App\Support\Timezone;

/**
 * Isi kartu darurat satu jalur (spec v3 bagian 10.2 dan 10.8; rencana induk W6 butir 8; ruling
 * W6-R4): 112 dan Basarnas 115 sebagai tautan telepon, kontak pengelola bila resmi diketahui,
 * dan kalimat tetap di tempat setelah menelepon. Blok lokasi dihitung di perangkat;
 * kartu ini tidak mengirim posisi ke mana pun. Rekam jejak yang opsional mengirim titiknya
 * sendiri sesudah pendakian selesai atau dibatalkan, saat ada sinyal, bukan lewat kartu ini,
 * sesuai persetujuan Profil (ruling W6-P2).
 *
 * Kontak pengelola hanya dari badan resmi tingkat gunung yang sudah diverifikasi dan kontaknya
 * tercatat; nomor tidak pernah ditebak (R-007). Pilihannya deterministik (ruling W6-7a,
 * pra-tinjau PT7-4): balai taman nasional lebih dulu, lalu instansi pemerintah, lalu id.
 * Pengelola basecamp dikecualikan sampai badan dapat ditautkan ke jalur (titipan W8): satu
 * badan basecamp dapat mewakili jalur lain di gunung yang sama, dan informasi gunung tidak
 * menggantikan informasi jalur (R-001, R-002). Karena itu layar menyebutnya "pengelola resmi
 * di {gunung}", bukan pengelola jalur.
 *
 * Zona dan labelnya ikut supaya jam pengukuran posisi dibacakan dalam zona gunung, bukan zona
 * ponsel (PT7-14).
 */
final class KartuDarurat
{
    /** Urutan jenis badan yang kontaknya dipakai; jenis lain tidak pernah menjadi kontak. */
    private const PRIORITAS = [AuthorityType::NATIONAL_PARK, AuthorityType::GOVERNMENT];

    /**
     * @return array{nomor: array<int, array{label: string, nomor: string}>, pengelola: array{nama: string, kontak: string, telepon: string|null}|null, tetap: string, zona: string, labelZona: string}
     */
    public function untuk(Trail $jalur): array
    {
        $jalur->loadMissing('mountain.authorities');
        $zona = Timezone::forTrail($jalur);

        $pengelola = $jalur->mountain?->authorities
            ->filter(fn (Authority $a) => in_array($a->type, self::PRIORITAS, true) && $a->verified_at !== null && filled($a->contact))
            ->sort(fn (Authority $a, Authority $b) => [self::peringkat($a), $a->id] <=> [self::peringkat($b), $b->id])
            ->first();

        return [
            'nomor' => [
                ['label' => 'Darurat', 'nomor' => '112'],
                ['label' => 'Basarnas', 'nomor' => '115'],
            ],
            'pengelola' => $pengelola === null ? null : [
                'nama' => $pengelola->displayName(),
                'kontak' => (string) $pengelola->contact,
                'telepon' => self::telepon((string) $pengelola->contact),
            ],
            'tetap' => 'Setelah menelepon, tetap di tempat kecuali tempat itu berbahaya. Petugas mencari dari lokasi yang Anda sebutkan.',
            'zona' => $zona,
            'labelZona' => Timezone::label($zona),
        ];
    }

    /**
     * Nomor untuk tautan tel: bila kontaknya memang nomor telepon; selain itu null, dan kontak
     * ditampilkan sebagai teks.
     */
    public static function telepon(string $kontak): ?string
    {
        $nomor = preg_replace('/[\s().-]/', '', $kontak);

        return preg_match('/^\+?\d{3,15}$/', (string) $nomor) === 1 ? $nomor : null;
    }

    private static function peringkat(Authority $badan): int
    {
        return (int) array_search($badan->type, self::PRIORITAS, true);
    }
}
