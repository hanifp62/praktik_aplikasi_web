<?php

namespace App\Services\Dashboard;

use App\Services\Community\KabarJalur;

/**
 * Kabar gunung yang diikuti di Beranda (spec v3 bagian 6.1, prototype 01; rencana induk W5
 * butir 3): ringkasan KabarJalur, status resmi dan laporan komunitas dalam dua kelompok yang
 * terpisah (R-010). Tidak dikirim sebelum profil terisi (B0: satu fokus, profil) dan selama
 * pendakian berjalan (B3: satu fokus, mode lapangan).
 *
 * Selama trip mendatang (B2) menuju gunung yang belum diikuti, Beranda menawarkan mengikutinya:
 * penutupan diumumkan untuk kawasan, dan tujuan trip adalah kawasan yang paling perlu diikuti.
 * Laporan komunitas dikirim dalam bentuk kartu laporan halaman gunung (KondisiPendaki), supaya
 * lapisan komunitas tampil sama di kedua tempat.
 */
final class KabarBeranda
{
    public function __construct(private readonly KabarJalur $kabar) {}

    /**
     * @return array{judul: string, mengikuti: string|null, diikuti: bool, resmi: array<int, array<string, mixed>>, komunitas: array<int, array<string, mixed>>, ikuti: array{gunung: string, href: string}|null}|null
     */
    public function untuk(KonteksBeranda $konteks): ?array
    {
        if (in_array($konteks->fase, [RingkasanDasbor::B0, RingkasanDasbor::B3], true)) {
            return null;
        }

        $ringkas = $this->kabar->ringkas($konteks->pengguna);
        $gunung = $ringkas['gunung'] ?? [];
        $tujuan = $konteks->trip?->trail->mountain;

        return [
            'judul' => count($gunung) === 1 ? 'Kabar '.reset($gunung).' yang Anda ikuti' : 'Kabar gunung yang Anda ikuti',
            'mengikuti' => count($gunung) > 1 ? collect($gunung)->join(', ', ' dan ') : null,
            'diikuti' => $ringkas !== null,
            'resmi' => $ringkas['resmi'] ?? [],
            'komunitas' => array_map(fn (array $b) => [
                'id' => $b['id'],
                'jalur' => $b['judul'],
                'tags' => $b['tag'],
                'catatan' => $b['catatan'],
                'dilaporkan' => $b['waktu'],
                'tanggalPendakian' => (string) $b['tanggalPendakian'],
                'kesegaran' => $b['kesegaran'],
            ], $ringkas['komunitas'] ?? []),
            'ikuti' => $tujuan !== null && ! array_key_exists($tujuan->id, $gunung)
                ? ['gunung' => $tujuan->name, 'href' => route('mountains.follow', $tujuan)]
                : null,
        ];
    }
}
