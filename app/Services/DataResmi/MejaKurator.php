<?php

namespace App\Services\DataResmi;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusVerificationState;
use App\Models\AuthorityMember;
use App\Models\DataErrorReport;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Models\VolcanoStatus;
use App\Services\DataFreshnessService;
use App\Support\Timezone;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Kelas halaman meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 9; ruling W8-R15):
 * antrean yang menunggu tindakan kurator ini, hanya di kawasan badannya. Verifikasi orang kedua
 * (catatan kurator lain yang menunggu), tinjau berkala (kedaluwarsa, segera kedaluwarsa, lewat
 * jadwal tinjau, termasuk catatan lama dari sebelum meja kurator), laporan kesalahan data dari
 * pendaki yang menunggu cek ulang (ruling W8-R17), catatan yang dipersoalkan, dan draf miliknya
 * sendiri. Jumlah kueri tetap, tidak tumbuh bersama jumlah catatan.
 */
final class MejaKurator
{
    public const BATAS = 50;

    public function __construct(
        private readonly Yurisdiksi $yurisdiksi,
        private readonly DataFreshnessService $kesegaran,
        private readonly StatusGunungApi $gunungApi,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(User $kurator): array
    {
        $lingkup = $this->lingkup($kurator);
        $tinjau = array_map(
            fn (EloquentCollection $daftar) => $daftar->take(self::BATAS)
                ->loadMorph('statusable', [Trail::class => ['mountain:id,name'], TrailSegment::class => ['trail:id,name,mountain_id']])
                ->load(['recordedBy:id,name', 'authority:id,name,abbreviation']),
            $this->kesegaran->review($lingkup),
        );
        $dasar = fn () => OfficialStatus::query()->where($lingkup)->with($this->muatan())->orderBy('created_at')->limit(self::BATAS);

        return [
            'title' => 'Meja kurator',
            'badan' => $this->yurisdiksi->keanggotaan($kurator)->map(fn (AuthorityMember $a) => [
                'nama' => $a->authority->displayName(),
                'peran' => $a->role->label(),
                'berlakuSampai' => $a->valid_until?->translatedFormat('j M Y'),
            ])->values()->all(),
            'gunungApi' => $this->gunungApi($kurator),
            'antrean' => [
                'verifikasi' => $dasar()
                    ->where('verification_state', StatusVerificationState::PENDING->value)
                    ->where('recorded_by', '!=', $kurator->id)
                    ->get()->map(fn (OfficialStatus $s) => $this->baris($s))->values()->all(),
                'tinjau' => [
                    ...$tinjau['kedaluwarsa']->map(fn (OfficialStatus $s) => $this->baris($s, 'Kedaluwarsa'))->all(),
                    ...$tinjau['segera']->map(fn (OfficialStatus $s) => $this->baris($s, 'Segera kedaluwarsa'))->all(),
                    ...$tinjau['basi']->map(fn (OfficialStatus $s) => $this->baris($s, 'Lewat jadwal tinjau'))->all(),
                ],
                'laporan' => DataErrorReport::query()
                    ->terbuka()
                    ->whereIn('mountain_id', $this->yurisdiksi->gunung($kurator)->all())
                    ->with(['mountain:id,name,slug', 'trail:id,name,slug,mountain_id'])
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->limit(self::BATAS)
                    ->get()
                    ->map(fn (DataErrorReport $l) => $this->barisLaporan($l))
                    ->values()
                    ->all(),
                'dipersoalkan' => $dasar()
                    ->where('verification_state', StatusVerificationState::DISPUTED->value)
                    ->get()->map(fn (OfficialStatus $s) => $this->baris($s))->values()->all(),
                'draf' => $dasar()
                    ->where('verification_state', StatusVerificationState::DRAFT->value)
                    ->where('recorded_by', $kurator->id)
                    ->get()->map(fn (OfficialStatus $s) => $this->baris($s))->values()->all(),
            ],
        ];
    }

    /**
     * Syarat "sasaran di kawasan badan kurator" untuk kueri catatan status: gunungnya, jalur di
     * gunung itu, dan segmen jalur itu. Tiga kueri id, sekali per halaman.
     *
     * @return Closure(Builder): void
     */
    public function lingkup(User $kurator): Closure
    {
        $gunung = $this->yurisdiksi->gunung($kurator)->all();
        $jalur = Trail::query()->whereIn('mountain_id', $gunung)->pluck('id')->all();
        $segmen = TrailSegment::query()->whereIn('trail_id', $jalur)->pluck('id')->all();

        return function (Builder $q) use ($gunung, $jalur, $segmen): void {
            $q->where(fn (Builder $g) => $g->where('statusable_type', (new Mountain)->getMorphClass())->whereIn('statusable_id', $gunung))
                ->orWhere(fn (Builder $t) => $t->where('statusable_type', (new Trail)->getMorphClass())->whereIn('statusable_id', $jalur))
                ->orWhere(fn (Builder $s) => $s->where('statusable_type', (new TrailSegment)->getMorphClass())->whereIn('statusable_id', $segmen));
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function muatan(): array
    {
        return [
            'statusable' => fn (MorphTo $m) => $m->morphWith([
                Trail::class => ['mountain:id,name'],
                TrailSegment::class => ['trail:id,name,mountain_id'],
            ]),
            'recordedBy:id,name',
            'authority:id,name,abbreviation',
        ];
    }

    /**
     * Level gunung api terakhir per gunung di kawasan kurator (DK-43; ruling W8-R18), hanya gunung
     * yang sudah punya catatan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function gunungApi(User $kurator): array
    {
        $terkini = EloquentCollection::make(array_values($this->gunungApi->terkiniUntuk($this->yurisdiksi->gunung($kurator))))
            ->load('mountain:id,name,timezone')
            ->sortBy(fn (VolcanoStatus $s) => $s->mountain->name);

        return $terkini->map(fn (VolcanoStatus $s) => [
            'id' => $s->id,
            'gunung' => $s->mountain->name,
            ...$this->gunungApi->bentuk($s, Timezone::isSupported($s->mountain->timezone) ? $s->mountain->timezone : Timezone::DEFAULT),
            'perbarui' => route('curator.volcano.create', ['gunung' => $s->mountain_id]),
        ])->values()->all();
    }

    /**
     * Satu laporan kesalahan data. Identitas pelapor tidak dikirim ke kurator (minimisasi data,
     * PRD §83): yang dicek adalah datanya, bukan orangnya.
     *
     * @return array<string, mixed>
     */
    private function barisLaporan(DataErrorReport $l): array
    {
        $jalur = $l->trail;

        return [
            'id' => $l->id,
            'sasaran' => $jalur !== null ? $jalur->name.', '.$l->mountain->name : $l->mountain->name.' (seluruh gunung)',
            'halaman' => $jalur !== null ? route('trails.show', $jalur) : route('mountains.show', $l->mountain),
            'bagian' => $l->category->label(),
            'isi' => $l->note,
            'waktu' => Timezone::display($l->created_at, Timezone::DEFAULT, 'j M Y H.i'),
            'catatan' => $l->official_status_id !== null ? route('curator.statuses.show', $l->official_status_id) : null,
            'cek' => route('curator.data-errors.check', $l),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function baris(OfficialStatus $s, ?string $alasanTinjau = null): array
    {
        $nilai = $s->status;

        return [
            'id' => $s->id,
            'sasaran' => HalamanCatatanStatus::namaSasaran($s->statusable),
            'status' => $nilai === OfficialStatusValue::UNKNOWN ? null : $nilai->value,
            'statusLabel' => $nilai->label(),
            'keadaan' => $s->verification_state?->label() ?? 'Catatan lama',
            'pencatat' => $s->recordedBy?->name,
            'badan' => $s->authority?->displayName() ?? $s->source,
            'berlaku' => HalamanCatatanStatus::masaBerlaku($s, Timezone::DEFAULT),
            'alasanTinjau' => $alasanTinjau,
            'sasaranKunci' => HalamanCatatanStatus::kunciSasaran($s->statusable),
            'url' => route('curator.statuses.show', $s),
        ];
    }
}
