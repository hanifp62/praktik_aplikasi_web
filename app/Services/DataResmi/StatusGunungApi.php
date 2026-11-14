<?php

namespace App\Services\DataResmi;

use App\Enums\VolcanoLevel;
use App\Models\VolcanoStatus;
use App\Services\DataFreshnessService;
use App\Support\Timezone;

/**
 * Status gunung api PVMBG untuk layar pendaki dan meja kurator (DK-43; rencana induk W8 butir 7;
 * ruling W8-R18). Yang tampil adalah pencatatan yang terbit paling akhir per gunung, selalu dengan
 * atribusi PVMBG dan tautan laporannya, dan hanya bila ada. Tidak pernah dibaca oleh status akses,
 * kecocokan, atau rekomendasi: level gunung api bukan Buka atau Tutup.
 */
final class StatusGunungApi
{
    public function __construct(private readonly DataFreshnessService $kesegaran) {}

    public function terkini(int $gunung): ?VolcanoStatus
    {
        return $this->terkiniUntuk([$gunung])[$gunung] ?? null;
    }

    /**
     * Pencatatan terakhir per gunung untuk sekumpulan gunung, satu kueri.
     *
     * @param  iterable<int, int>  $gunung
     * @return array<int, VolcanoStatus> berkunci id gunung
     */
    public function terkiniUntuk(iterable $gunung): array
    {
        $id = collect($gunung)->filter()->unique()->values()->all();

        if ($id === []) {
            return [];
        }

        return VolcanoStatus::query()
            ->whereIn('mountain_id', $id)
            ->where('issued_at', fn ($q) => $q->selectRaw('max(v.issued_at)')->from('volcano_statuses as v')->whereColumn('v.mountain_id', 'volcano_statuses.mountain_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('mountain_id')
            ->keyBy('mountain_id')
            ->all();
    }

    /**
     * Bentuk untuk halaman, atau null bila gunung itu belum punya catatan (tampil hanya bila ada).
     *
     * @return array{level: int, label: string, rekomendasi: string, atribusi: string, tautan: string, diterbitkan: string, diverifikasi: string, kesegaran: string}|null
     */
    public function bentuk(?VolcanoStatus $s, string $zona): ?array
    {
        if ($s === null) {
            return null;
        }

        $jam = (int) config('hiking.freshness.volcano_review_hours', 168);

        return [
            'level' => $s->level->value,
            'label' => $s->level->label(),
            'rekomendasi' => $s->recommendation,
            'atribusi' => VolcanoLevel::ATRIBUSI,
            'tautan' => $s->report_url,
            'diterbitkan' => (string) Timezone::display($s->issued_at, $zona, 'j M Y H.i'),
            'diverifikasi' => (string) Timezone::display($s->verified_at, $zona, 'j M Y H.i'),
            'kesegaran' => $this->kesegaran->keadaanDari($s->verified_at, null, null, $s->verified_at->copy()->addHours($jam)),
        ];
    }

    /**
     * Satu gunung, langsung dalam bentuk halaman.
     *
     * @return array{level: int, label: string, rekomendasi: string, atribusi: string, tautan: string, diterbitkan: string, diverifikasi: string, kesegaran: string}|null
     */
    public function untukGunung(int $gunung, string $zona): ?array
    {
        return $this->bentuk($this->terkini($gunung), $zona);
    }
}
