<?php

namespace App\Services\Trails;

use App\Models\Trail;
use Illuminate\Support\Collection;

/**
 * Pencarian jalur katalog untuk isian yang menunjuk satu jalur (W7): catatan pendakian di luar
 * trip dan jalur terberat di profil pendaki. Hanya jalur terbit (R-022), dengan pencocokan kata
 * kunci milik Jelajah, yang tidak peka huruf besar di SQLite maupun PostgreSQL (TrailSearch,
 * R-024). Kata kunci di bawah dua huruf tidak mencari apa pun, jadi katalog tidak pernah
 * dikirim utuh; hasilnya paling banyak BATAS jalur, urut nama.
 */
final class CariJalur
{
    public const BATAS = 8;

    /**
     * @return Collection<int, Trail>
     */
    public function untuk(string $kata): Collection
    {
        $kata = trim($kata);

        if (mb_strlen($kata) < 2) {
            return collect();
        }

        return TrailSearch::dariInput(['search' => mb_substr($kata, 0, 80)])->kandidat(self::BATAS)->take(self::BATAS)->values();
    }

    /**
     * Satu baris hasil: nama, gunung, dan baris ukuran padat jalur itu.
     *
     * @return array{id: int, nama: string, gunung: string|null, ringkas: string}
     */
    public static function baris(Trail $jalur): array
    {
        return [
            'id' => $jalur->id,
            'nama' => $jalur->name,
            'gunung' => $jalur->mountain?->name,
            'ringkas' => TrailFigures::ringkas($jalur),
        ];
    }
}
