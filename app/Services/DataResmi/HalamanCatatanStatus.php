<?php

namespace App\Services\DataResmi;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\StatusVerificationState;
use App\Models\AuditLog;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Kelas halaman formulir dan rincian satu catatan meja kurator (spec v3 bagian 10.7, alur tujuh
 * langkah; ruling W8-R15). Formulir hanya menawarkan sasaran dan badan di yurisdiksi kurator
 * sesi; server memeriksanya ulang (SimpanCatatanStatusRequest). Rincian menyebut langkah yang
 * sudah dan belum dilalui, temuan pemeriksaan konsistensi, arsip bukti beserta keutuhannya,
 * rantai penggantian, jejak audit catatan itu, dan aksi yang boleh diambil kurator sesi menurut
 * Policy.
 */
final class HalamanCatatanStatus
{
    private const BATAS_JEJAK = 20;

    public function __construct(
        private readonly Yurisdiksi $yurisdiksi,
        private readonly PemeriksaanKonsistensi $konsistensi,
        private readonly ArsipBukti $arsip,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function formulir(User $kurator, ?OfficialStatus $draf, ?string $sasaranAwal): array
    {
        return [
            'title' => $draf === null ? 'Catat status resmi' : 'Ubah draf status resmi',
            'draf' => $draf === null ? null : [
                'id' => $draf->id,
                'sasaran' => self::namaSasaran($draf->statusable),
                'badan' => $draf->authority?->displayName(),
                'isian' => $this->isianDraf($draf),
                'adaArsip' => $draf->evidence_archive_path !== null,
                'url' => route('curator.statuses.show', $draf),
            ],
            'sasaran' => $draf === null ? $this->pilihanSasaran($kurator) : [],
            'sasaranAwal' => $sasaranAwal,
            'pilihan' => [
                'status' => array_map(fn (OfficialStatusValue $n) => ['value' => $n->value, 'label' => $n->label()], OfficialStatusValue::cases()),
                'jenisBukti' => array_map(fn (EvidenceType $j) => ['value' => $j->value, 'label' => $j->label().($j->isPrimary() ? '' : ' (bukan bukti primer)')], EvidenceType::cases()),
                'caraBerakhir' => array_map(fn (StatusEndCondition $c) => ['value' => $c->value, 'label' => $c->label()], StatusEndCondition::cases()),
                'golonganAlasan' => array_map(fn (StatusReasonCategory $g) => ['value' => $g->value, 'label' => $g->label()], StatusReasonCategory::cases()),
            ],
            'batasBuktiKb' => (int) config('hiking.uploads.evidence_max_kb'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rincian(User $kurator, OfficialStatus $s): array
    {
        $s->loadMissing(['statusable', 'authority', 'recordedBy:id,name', 'verifiedBy:id,name', 'replaces', 'supersededBy', 'dataSource:id,source_type']);
        $zona = self::zona($s->statusable);
        $gerbang = Gate::forUser($kurator);
        $nilai = $s->status;

        return [
            'title' => 'Catatan status #'.$s->id,
            'catatan' => [
                'id' => $s->id,
                'sasaran' => self::namaSasaran($s->statusable),
                'status' => $nilai === OfficialStatusValue::UNKNOWN ? null : $nilai->value,
                'statusLabel' => $nilai->label(),
                'keadaan' => $s->verification_state?->label() ?? 'Catatan lama (sebelum meja kurator)',
                'terlihatPendaki' => $s->terlihatPendaki(),
                'menungguVerifikasiKedua' => $s->menungguVerifikasiKedua(),
                'badan' => $s->authority?->displayName() ?? $s->source,
                'surat' => trim(($s->document_number ?? '').($s->document_date !== null ? ', '.$s->document_date->translatedFormat('j M Y') : ''), ', ') ?: null,
                'jenisBukti' => $s->evidence_type?->label(),
                'urlSumber' => $s->source_url,
                'ditangkap' => Timezone::display($s->captured_at, $zona, 'j M Y H.i'),
                'berlaku' => self::masaBerlaku($s, $zona),
                'caraBerakhir' => $s->end_condition?->label(),
                'batas' => $s->limit_description,
                'alasan' => trim(($s->reason_category?->label() ?? '').($s->reason ? ': '.$s->reason : ''), ': ') ?: null,
                'dasarHukum' => $s->legal_basis,
                'kutipan' => $s->official_excerpt,
                'ringkasan' => $s->plain_summary,
                'pencatat' => $s->recordedBy?->name,
                'pemeriksa' => $s->verifiedBy?->name,
                'diverifikasi' => Timezone::display($s->verified_at, $zona, 'j M Y H.i'),
                'tinjauBerikutnya' => Timezone::display($s->next_review_at, $zona, 'j M Y H.i'),
            ],
            'arsip' => $s->evidence_archive_path === null ? null : [
                'url' => route('curator.evidence.show', $s),
                'sidik' => substr((string) $s->evidence_sha256, 0, 12),
                'utuh' => $this->arsip->utuh($s),
            ],
            'langkah' => $this->langkah($s),
            'temuan' => $s->verification_state === null || $s->superseded_by_id !== null ? [] : $this->konsistensi->periksa($s),
            'rantai' => [
                'menggantikan' => $s->replaces === null ? null : ['id' => $s->replaces->id, 'url' => route('curator.statuses.show', $s->replaces)],
                'digantikan' => $s->supersededBy === null ? null : ['id' => $s->supersededBy->id, 'url' => route('curator.statuses.show', $s->supersededBy)],
            ],
            'jejak' => AuditLog::query()
                ->where('entity_type', $s->getMorphClass())
                ->where('entity_id', $s->id)
                ->where('action', 'like', 'official_status.%')
                ->with('actor:id,name')
                ->latest('id')
                ->limit(self::BATAS_JEJAK)
                ->get()
                ->map(fn (AuditLog $l) => [
                    'id' => $l->id,
                    'tindakan' => self::kataTindakan($l->action),
                    'oleh' => $l->actor?->name ?? 'Sistem',
                    'waktu' => Timezone::display($l->created_at, $zona, 'j M Y H.i'),
                    'alasan' => is_array($l->after) ? ($l->after['alasan'] ?? null) : null,
                ])->values()->all(),
            'aksi' => [
                'ubah' => $gerbang->allows('updateDraft', $s) ? route('curator.statuses.edit', $s) : null,
                'kirim' => $gerbang->allows('updateDraft', $s) ? route('curator.statuses.submit', $s) : null,
                'hapus' => $gerbang->allows('updateDraft', $s) ? route('curator.statuses.destroy', $s) : null,
                'verifikasi' => $gerbang->allows('verify', $s) ? route('curator.statuses.verify', $s) : null,
                'persoalkan' => $gerbang->allows('dispute', $s) ? route('curator.statuses.dispute', $s) : null,
                'tinjauUlang' => $gerbang->allows('review', $s) ? route('curator.statuses.review', $s) : null,
                'catatPengganti' => $s->statusable !== null && $s->verification_state !== StatusVerificationState::DRAFT
                    ? route('curator.statuses.create', ['sasaran' => self::kunciSasaran($s->statusable)])
                    : null,
            ],
            'meja' => route('curator.desk'),
        ];
    }

    /**
     * Langkah 1 sampai 5 alur meja kurator untuk catatan ini; langkah 6 (tinjau berkala) dan 7
     * (konflik) berupa antrean di meja.
     *
     * @return array<int, array{nomor: int, nama: string, selesai: bool, keterangan: string}>
     */
    private function langkah(OfficialStatus $s): array
    {
        $keadaan = $s->verification_state;
        $lama = $keadaan === null;
        $terkirim = in_array($keadaan, [StatusVerificationState::PENDING, StatusVerificationState::VERIFIED, StatusVerificationState::DISPUTED], true);

        return [
            ['nomor' => 1, 'nama' => 'Masukan', 'selesai' => $lama || $s->evidence_archive_path !== null, 'keterangan' => $s->evidence_archive_path !== null ? 'Arsip bukti tersimpan.' : 'Belum ada salinan bukti.'],
            ['nomor' => 2, 'nama' => 'Penstrukturan', 'selesai' => true, 'keterangan' => 'Penerbit, sasaran, nilai, dan masa berlaku tercatat.'],
            ['nomor' => 3, 'nama' => 'Pemeriksaan konsistensi', 'selesai' => $lama || $terkirim, 'keterangan' => $terkirim ? 'Lolos saat dikirim.' : 'Diperiksa saat dikirim.'],
            ['nomor' => 4, 'nama' => 'Verifikasi orang kedua', 'selesai' => $lama || $keadaan === StatusVerificationState::VERIFIED, 'keterangan' => match (true) {
                $lama => 'Catatan lama, sebelum meja kurator.',
                $keadaan === StatusVerificationState::VERIFIED => 'Diverifikasi '.($s->verifiedBy?->name ?? 'kurator kedua').'.',
                $keadaan === StatusVerificationState::DISPUTED => 'Dipersoalkan.',
                default => 'Menunggu kurator kedua.',
            }],
            ['nomor' => 5, 'nama' => 'Terbit', 'selesai' => $s->terlihatPendaki(), 'keterangan' => $s->terlihatPendaki()
                ? ($s->menungguVerifikasiKedua() ? 'Terbaca pendaki, bertanda menunggu verifikasi kedua.' : 'Terbaca pendaki.')
                : 'Belum terbaca pendaki.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function isianDraf(OfficialStatus $s): array
    {
        $zona = self::zona($s->statusable);

        return [
            'status' => $s->status->value,
            'jenis_bukti' => $s->evidence_type?->value ?? EvidenceType::OFFICIAL_LETTER->value,
            'url_sumber' => $s->source_url ?? '',
            'waktu_tangkap' => $s->captured_at?->copy()->setTimezone($zona)->format('Y-m-d\TH:i') ?? '',
            'nomor_surat' => $s->document_number ?? '',
            'tanggal_surat' => $s->document_date?->toDateString() ?? '',
            'mulai_berlaku' => $s->effective_at?->copy()->setTimezone($zona)->toDateString() ?? '',
            'cara_berakhir' => $s->end_condition?->value ?? StatusEndCondition::NOT_STATED->value,
            'berlaku_sampai' => $s->expires_at?->copy()->setTimezone($zona)->toDateString() ?? '',
            'batas' => $s->limit_description ?? '',
            'golongan_alasan' => $s->reason_category?->value ?? '',
            'alasan' => $s->reason ?? '',
            'dasar_hukum' => $s->legal_basis ?? '',
            'kutipan' => $s->official_excerpt ?? '',
            'ringkasan' => $s->plain_summary ?? '',
        ];
    }

    /**
     * Sasaran di kawasan badan kurator, dikelompokkan per gunung, beserta badan yang boleh
     * dipilih sebagai penerbit untuk sasaran itu.
     *
     * @return array<int, array{gunung: string, badan: array<int, array{value: int, label: string}>, pilihan: array<int, array{value: string, label: string}>}>
     */
    private function pilihanSasaran(User $kurator): array
    {
        $keanggotaan = $this->yurisdiksi->keanggotaan($kurator);
        $gunung = Mountain::query()
            ->whereKey($this->yurisdiksi->gunung($kurator)->all())
            ->with(['trails' => fn ($q) => $q->active()->orderBy('name')->with('segments:id,trail_id,name,sequence')])
            ->orderBy('name')
            ->get();

        return $gunung->map(function (Mountain $m) use ($keanggotaan) {
            $badan = $keanggotaan
                ->filter(fn (AuthorityMember $a) => $a->authority->mountains->contains('id', $m->id))
                ->map(fn (AuthorityMember $a) => ['value' => $a->authority->id, 'label' => $a->authority->displayName()])
                ->unique('value')->values()->all();

            $pilihan = [['value' => 'MOUNTAIN:'.$m->id, 'label' => $m->name.' (seluruh gunung)']];

            foreach ($m->trails as $jalur) {
                $pilihan[] = ['value' => 'TRAIL:'.$jalur->id, 'label' => $jalur->name];

                foreach ($jalur->segments as $segmen) {
                    $pilihan[] = ['value' => 'SEGMENT:'.$segmen->id, 'label' => $jalur->name.', segmen '.$segmen->name];
                }
            }

            return ['gunung' => $m->name, 'badan' => $badan, 'pilihan' => $pilihan];
        })->values()->all();
    }

    public static function namaSasaran(?Model $sasaran): string
    {
        return match (true) {
            $sasaran instanceof Mountain => $sasaran->name.' (seluruh gunung)',
            $sasaran instanceof Trail => $sasaran->name.($sasaran->mountain ? ', '.$sasaran->mountain->name : ''),
            $sasaran instanceof TrailSegment => ($sasaran->trail?->name ?? 'Jalur').', segmen '.$sasaran->name,
            default => 'Sasaran terhapus',
        };
    }

    public static function kunciSasaran(?Model $sasaran): ?string
    {
        return match (true) {
            $sasaran instanceof Mountain => 'MOUNTAIN:'.$sasaran->id,
            $sasaran instanceof Trail => 'TRAIL:'.$sasaran->id,
            $sasaran instanceof TrailSegment => 'SEGMENT:'.$sasaran->id,
            default => null,
        };
    }

    public static function masaBerlaku(OfficialStatus $s, string $zona): string
    {
        $mulai = $s->effective_at === null ? 'Tanggal mulai tidak disebut' : 'Mulai '.Timezone::display($s->effective_at, $zona, 'j M Y');

        return match (true) {
            $s->superseded_by_id !== null && $s->expires_at !== null => $mulai.', digantikan '.Timezone::display($s->expires_at, $zona, 'j M Y'),
            $s->expires_at !== null => $mulai.' sampai '.Timezone::display($s->expires_at, $zona, 'j M Y'),
            $s->end_condition === StatusEndCondition::UNTIL_NOTICE => $mulai.', sampai pemberitahuan lebih lanjut',
            default => $mulai.', berakhirnya tidak disebut',
        };
    }

    public static function zona(?Model $sasaran): string
    {
        $gunung = match (true) {
            $sasaran instanceof Mountain => $sasaran,
            $sasaran instanceof Trail => $sasaran->mountain,
            $sasaran instanceof TrailSegment => $sasaran->trail?->mountain,
            default => null,
        };

        return Timezone::isSupported($gunung?->timezone) ? $gunung->timezone : Timezone::DEFAULT;
    }

    private static function kataTindakan(string $aksi): string
    {
        return match ($aksi) {
            'official_status.drafted' => 'Draf dibuat',
            'official_status.draft_updated' => 'Draf diubah',
            'official_status.submitted' => 'Dikirim',
            'official_status.verified' => 'Diverifikasi orang kedua',
            'official_status.disputed' => 'Dipersoalkan',
            'official_status.reviewed' => 'Dicek ulang ke sumbernya',
            'official_status.superseded' => 'Digantikan catatan baru',
            default => 'Diubah',
        };
    }
}
