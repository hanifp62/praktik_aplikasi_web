<?php

namespace App\Services\Admin;

use App\Models\Trail;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ringkasan kesehatan data jalur untuk konsol pengelola: apa yang perlu diurus lebih
 * dulu, dihitung dari syarat publikasi yang sama dengan gerbang §110.
 *
 * Setiap temuan sekaligus saringan atas jalur aktif, sehingga angka di ringkasan dan isi
 * daftar yang dibuka dari angka itu tidak pernah berbeda. Kalimatnya kalimat
 * publishabilityReport(), supaya kurator membaca hal yang sama di ringkasan dan di baris
 * jalurnya.
 *
 * Geometri hanya dapat diperiksa di PostGIS. Pada koneksi lain temuannya tidak dihitung
 * sama sekali, bukan dilaporkan nol: tidak diketahui bukan berarti lengkap (R-006).
 */
class TrailDataHealth
{
    /**
     * Urutan adalah urutan urgensi. Jalur yang tayang padahal belum memenuhi syarat
     * sedang dibaca pendaki hari ini, jadi ia paling atas.
     *
     * @return array<string, array{label: string, lingkup: Closure(Builder<Trail>): mixed}>
     */
    private function kriteria(): array
    {
        $kriteria = [
            'tayang-belum-lengkap' => [
                'label' => 'Tayang padahal belum memenuhi syarat',
                'lingkup' => fn (Builder $query) => $query
                    ->where('is_published', true)
                    ->whereNot(fn (Builder $lengkap) => $lengkap->dataComplete()),
            ],
        ];

        if (Trail::spatialSupported()) {
            $kriteria['tanpa-geometri'] = [
                'label' => 'Geometri jalur belum tersedia',
                'lingkup' => fn (Builder $query) => $query->whereNull('geometry'),
            ];
        }

        return $kriteria + [
            'tanpa-checkpoint' => [
                'label' => 'Jalur belum memiliki checkpoint',
                'lingkup' => fn (Builder $query) => $query->whereDoesntHave('checkpoints'),
            ],
            'tanpa-status' => [
                'label' => 'Status resmi belum pernah dicatat',
                'lingkup' => fn (Builder $query) => $query->whereDoesntHave('officialStatuses'),
            ],
            'karakteristik' => [
                'label' => 'Karakteristik dasar belum lengkap (jarak, elevation gain, estimasi durasi)',
                'lingkup' => fn (Builder $query) => $query->where(fn (Builder $kosong) => $kosong
                    ->whereNull('distance_km')
                    ->orWhereNull('elevation_gain_m')
                    ->orWhereNull('estimated_duration_minutes')),
            ],
            'tanpa-sumber' => [
                'label' => 'Sumber data belum ditetapkan',
                'lingkup' => fn (Builder $query) => $query->whereNull('data_source_id'),
            ],
        ];
    }

    /**
     * Satu query hitungan per temuan: jumlahnya tetap, tidak tumbuh bersama jumlah jalur.
     *
     * @return array<int, array{kunci: string, label: string, jumlah: int}>
     */
    public function ringkasan(): array
    {
        $hasil = [];

        foreach ($this->kriteria() as $kunci => $kriteria) {
            $query = Trail::query()->active();
            $kriteria['lingkup']($query);

            $hasil[] = ['kunci' => $kunci, 'label' => $kriteria['label'], 'jumlah' => $query->count()];
        }

        return $hasil;
    }

    public function label(?string $kunci): ?string
    {
        return $this->kriteria()[$kunci ?? '']['label'] ?? null;
    }

    /**
     * Menyaring daftar jalur ke satu temuan. Kunci yang tidak dikenal tidak menyaring
     * apa pun, sehingga tautan lama yang basi tetap membuka daftar lengkap.
     *
     * @param  Builder<Trail>  $query
     * @return Builder<Trail>
     */
    public function saring(Builder $query, ?string $kunci): Builder
    {
        $kriteria = $this->kriteria()[$kunci ?? ''] ?? null;

        if ($kriteria !== null) {
            $query->whereNull('archived_at');
            $kriteria['lingkup']($query);
        }

        return $query;
    }
}
