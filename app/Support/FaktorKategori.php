<?php

namespace App\Support;

/**
 * Skor faktor menjadi tiga kategori, dihitung di server sebelum sampai ke klien.
 *
 * BR-09 melarang skor internal sampai ke pengguna, dan batang selebar 73% dapat dibaca
 * balik menjadi angka sama persis seperti menuliskannya. Halaman React hanya menerima
 * kategorinya; skor dan bobotnya berhenti di sini.
 *
 * Ambangnya diambil dari config yang sudah dipakai isStrong() dan isWeak() di mesin,
 * bukan angka baru untuk tampilan.
 */
final class FaktorKategori
{
    /**
     * @param  array<int, array<string, mixed>>|null  $factors  isi matched_factors
     * @return array<int, array{label: string, detail: string|null, kategori: string}>
     */
    public static function dari(?array $factors): array
    {
        $kuat = (float) config('hiking.route_fit.strong_factor_threshold');
        $lemah = (float) config('hiking.route_fit.weak_factor_threshold');

        $baris = [];

        foreach ((array) $factors as $f) {
            if (! isset($f['label'])) {
                continue;
            }

            $skor = (float) ($f['score'] ?? 0);

            // §95: faktor yang datanya belum ada tidak digambar sebagai batang pendek.
            $kategori = match (true) {
                (bool) ($f['is_unknown'] ?? false) => 'tak-diketahui',
                $skor >= $kuat => 'kuat',
                $skor >= $lemah => 'cukup',
                default => 'lemah',
            };

            $baris[] = [
                'label' => (string) $f['label'],
                'detail' => isset($f['detail']) ? (string) $f['detail'] : null,
                'kategori' => $kategori,
            ];
        }

        return $baris;
    }
}
