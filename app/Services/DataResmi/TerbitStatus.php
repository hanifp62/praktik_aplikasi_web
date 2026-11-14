<?php

namespace App\Services\DataResmi;

use App\Enums\EvidenceType;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\StatusVerificationState;
use App\Models\Authority;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DataFreshnessService;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Layanan terbit status resmi dari meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 3;
 * ruling W8-R6, W8-R10, dan W8-R11). Satu-satunya jalan tulis status resmi baru: aturan dua orang
 * dan aturan terbit asimetris ditegakkan di sini, bukan di controller, jadi setiap pemanggil
 * memakai aturan yang sama, dan setiap langkah memeriksa OfficialStatusPolicy sendiri.
 *
 * - Pencatat, penerbit (`authority_id`), pemeriksa, dan keadaan selalu dari sesi dan Policy; isian
 *   hanya memberi isi catatan (kolom yang dapat diisi massal di OfficialStatus).
 * - Kirim: draf menjadi menunggu verifikasi kedua. Catatan yang membatasi (tidak lebih longgar dari
 *   status yang terlihat pada tanggal mulainya) dengan bukti primer terbit seketika; selain itu
 *   tidak terlihat sampai orang kedua memverifikasinya. Berita media saja tidak pernah membuka.
 * - Saat sebuah catatan mulai terlihat, catatan terlihat lain untuk sasaran yang sama yang sudah
 *   mulai sebelumnya ditandai digantikan dan berakhir pada tanggal mulai catatan baru.
 * - Setiap langkah tercatat di jejak audit dengan nilai sebelum dan sesudah (PRD §61). Cache
 *   snapshot jalur dibuang event model OfficialStatus.
 */
final class TerbitStatus
{
    /**
     * Kolom isi catatan yang diterima dari isian tervalidasi; sisanya ditulis layanan ini.
     */
    private const ISI = [
        'status', 'document_number', 'document_date', 'evidence_type', 'source_url', 'captured_at',
        'effective_at', 'end_condition', 'expires_at', 'reason_category', 'reason', 'limit_description',
        'legal_basis', 'official_excerpt', 'plain_summary',
    ];

    /**
     * Kolom yang dicatat jejak audit untuk setiap langkah.
     */
    private const DIAUDIT = [
        'status', 'verification_state', 'visible_before_verification', 'verified_by', 'verified_at',
        'effective_at', 'expires_at', 'evidence_sha256', 'superseded_by_id', 'next_review_at',
    ];

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly ArsipBukti $arsip,
        private readonly ArahPerubahan $arah,
        private readonly PemeriksaanKonsistensi $konsistensi,
        private readonly DataFreshnessService $kesegaran,
    ) {}

    /**
     * @param  array<string, mixed>  $isian  kolom isi catatan, sudah divalidasi
     */
    public function buatDraf(User $kurator, Model $sasaran, Authority $otoritas, array $isian, ?UploadedFile $bukti): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('record', [OfficialStatus::class, $sasaran, $otoritas]);

        return DB::transaction(function () use ($kurator, $sasaran, $otoritas, $isian, $bukti): OfficialStatus {
            $draf = (new OfficialStatus)->fill(Arr::only($isian, self::ISI))->forceFill([
                'statusable_type' => $sasaran->getMorphClass(),
                'statusable_id' => $sasaran->getKey(),
                'scope' => self::cakupan($sasaran),
                'authority_id' => $otoritas->id,
                'recorded_by' => $kurator->id,
                'verification_state' => StatusVerificationState::DRAFT,
                'visible_before_verification' => false,
            ]);
            $this->turunkanSumber($draf, $otoritas);

            if ($bukti !== null) {
                $this->arsipkan($draf, $bukti);
            }

            $draf->save();
            $this->audit->record($kurator, 'official_status.drafted', $draf, null, $draf->only(self::DIAUDIT));

            return $draf;
        });
    }

    /**
     * @param  array<string, mixed>  $isian
     */
    public function ubahDraf(User $kurator, OfficialStatus $draf, array $isian, ?UploadedFile $bukti): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('updateDraft', $draf);

        return DB::transaction(function () use ($kurator, $draf, $isian, $bukti): OfficialStatus {
            $sebelum = $draf->only(self::DIAUDIT);
            $arsipLama = $draf->evidence_archive_path;
            $draf->fill(Arr::only($isian, self::ISI));
            $this->turunkanSumber($draf, $draf->authority);

            if ($bukti !== null) {
                $this->arsipkan($draf, $bukti);
            }

            $draf->save();

            if ($bukti !== null) {
                $this->buangArsip($arsipLama);
            }

            $this->audit->record($kurator, 'official_status.draft_updated', $draf, $sebelum, $draf->only(self::DIAUDIT));

            return $draf;
        });
    }

    public function hapusDraf(User $kurator, OfficialStatus $draf): void
    {
        Gate::forUser($kurator)->authorize('updateDraft', $draf);

        DB::transaction(function () use ($kurator, $draf): void {
            $this->audit->record($kurator, 'official_status.draft_deleted', $draf, $draf->only(self::DIAUDIT), null);
            $draf->delete();
        });

        $this->buangArsip($draf->evidence_archive_path);
    }

    /**
     * Kirim draf untuk terbit (aturan terbit asimetris).
     */
    public function kirim(User $kurator, OfficialStatus $draf): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('updateDraft', $draf);

        if (! $this->arsip->utuh($draf)) {
            throw ValidationException::withMessages(['bukti' => 'Unggah salinan bukti sebelum mengirim: surat, laman, atau unggahan akun resmi pengelola.']);
        }

        $membuka = $this->arah->membuka($draf);

        if ($membuka && $draf->evidence_type === EvidenceType::NEWS) {
            throw ValidationException::withMessages(['evidence_type' => 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.']);
        }

        $this->tolakBilaBlokir($draf);

        return DB::transaction(function () use ($kurator, $draf, $membuka): OfficialStatus {
            $sebelum = $draf->only(self::DIAUDIT);
            $terlihat = ! $membuka && $draf->evidence_type?->isPrimary() === true;

            $draf->forceFill([
                'verification_state' => StatusVerificationState::PENDING,
                'visible_before_verification' => $terlihat,
                // Kurator mencocokkan catatan dengan sumbernya saat mengirim (spec v3 bagian 10.3).
                'verified_at' => now(),
                'fetched_at' => $draf->captured_at ?? now(),
                'next_review_at' => $this->kesegaran->jadwalUntuk($draf->status, now(), $draf->expires_at),
            ])->save();

            if ($terlihat) {
                $this->gantikan($kurator, $draf);
            }

            $this->audit->record($kurator, 'official_status.submitted', $draf, $sebelum, $draf->only(self::DIAUDIT));

            return $draf;
        });
    }

    /**
     * Verifikasi orang kedua: catatan terbit (bila belum) dan menggantikan catatan lama.
     */
    public function verifikasi(User $pemeriksa, OfficialStatus $catatan): OfficialStatus
    {
        Gate::forUser($pemeriksa)->authorize('verify', $catatan);

        if (! $this->arsip->utuh($catatan)) {
            throw ValidationException::withMessages(['bukti' => 'Arsip bukti tidak lagi cocok dengan sidiknya. Minta pencatat mengunggah ulang salinannya.']);
        }

        if ($catatan->evidence_type === EvidenceType::NEWS && $this->arah->membuka($catatan)) {
            throw ValidationException::withMessages(['evidence_type' => 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.']);
        }

        $this->tolakBilaBlokir($catatan);

        return DB::transaction(function () use ($pemeriksa, $catatan): OfficialStatus {
            $sebelum = $catatan->only(self::DIAUDIT);
            $sudahTerlihat = $catatan->terlihatPendaki();

            $catatan->forceFill([
                'verification_state' => StatusVerificationState::VERIFIED,
                'verified_by' => $pemeriksa->id,
                'verified_at' => now(),
                'next_review_at' => $this->kesegaran->jadwalUntuk($catatan->status, now(), $catatan->expires_at),
            ])->save();

            if (! $sudahTerlihat) {
                $this->gantikan($pemeriksa, $catatan);
            }

            $this->audit->record($pemeriksa, 'official_status.verified', $catatan, $sebelum, $catatan->only(self::DIAUDIT));

            return $catatan;
        });
    }

    /**
     * Tinjau berkala (spec v3 bagian 10.7 langkah 6): kurator mencocokkan ulang catatan yang
     * sedang terbaca dengan sumbernya dan keadaannya masih sama. Waktu verifikasi dan jadwal
     * tinjau berikutnya diperbarui; nilai catatan tidak berubah. Bila keadaannya berubah, kurator
     * mencatat catatan baru yang menggantikannya, bukan memakai langkah ini.
     */
    public function tinjauUlang(User $kurator, OfficialStatus $catatan): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('review', $catatan);

        return DB::transaction(function () use ($kurator, $catatan): OfficialStatus {
            $sebelum = $catatan->only(self::DIAUDIT);
            $catatan->forceFill([
                'verified_at' => now(),
                'next_review_at' => $this->kesegaran->jadwalUntuk($catatan->status, now(), $catatan->expires_at),
            ])->save();
            $this->audit->record($kurator, 'official_status.reviewed', $catatan, $sebelum, $catatan->only(self::DIAUDIT));

            return $catatan;
        });
    }

    /**
     * Mempersoalkan catatan yang menunggu atau sudah terverifikasi. Pembatasan yang sudah terbit
     * tetap terbaca; pembukaan berhenti terbaca, dan catatan yang digantikannya tidak hidup
     * kembali. Alasannya hanya tersimpan di jejak audit, tidak di catatan yang dibaca pendaki.
     */
    public function persoalkan(User $kurator, OfficialStatus $catatan, string $alasan): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('dispute', $catatan);

        return DB::transaction(function () use ($kurator, $catatan, $alasan): OfficialStatus {
            $sebelum = $catatan->only(self::DIAUDIT);
            $catatan->forceFill(['verification_state' => StatusVerificationState::DISPUTED])->save();
            $this->audit->record($kurator, 'official_status.disputed', $catatan, $sebelum, $catatan->only(self::DIAUDIT) + ['alasan' => $alasan]);

            return $catatan;
        });
    }

    /**
     * Temuan pemeriksaan konsistensi tingkat blokir menahan kiriman dan verifikasi (ruling
     * W8-R12), apa pun jalan tulisnya.
     */
    private function tolakBilaBlokir(OfficialStatus $catatan): void
    {
        $blokir = $this->konsistensi->blokir($catatan);

        if ($blokir !== []) {
            throw ValidationException::withMessages(['konsistensi' => $blokir]);
        }
    }

    public static function cakupan(Model $sasaran): string
    {
        return match (true) {
            $sasaran instanceof Mountain => StatusScope::MOUNTAIN->value,
            $sasaran instanceof TrailSegment => StatusScope::SEGMENT->value,
            default => StatusScope::TRAIL->value,
        };
    }

    /**
     * Catatan terlihat lain untuk sasaran yang sama yang sudah mulai sebelum catatan baru mulai,
     * dan belum berakhir saat itu, ditandai digantikan dan berakhir pada tanggal mulai itu.
     */
    private function gantikan(User $pelaku, OfficialStatus $baru): void
    {
        $mulai = $baru->effective_at ?? $baru->created_at;

        $lama = OfficialStatus::query()
            ->where('statusable_type', $baru->statusable_type)
            ->where('statusable_id', $baru->statusable_id)
            ->whereKeyNot($baru->id)
            ->whereNull('superseded_by_id')
            ->terlihatPendaki()
            ->dimulaiPalingLambat($mulai)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $mulai))
            ->get();

        foreach ($lama as $catatan) {
            $sebelum = $catatan->only(['expires_at', 'superseded_by_id']);
            $catatan->forceFill(['superseded_by_id' => $baru->id, 'expires_at' => $mulai])->save();
            $this->audit->record($pelaku, 'official_status.superseded', $catatan, $sebelum, $catatan->only(['expires_at', 'superseded_by_id']));
        }
    }

    /**
     * Sumber data yang dikutip catatan (R-009): bukti dari pengelola sendiri dikutip sebagai Resmi
     * atas nama badannya; berita atas pengumuman pengelola sebagai terverifikasi admin, seperti
     * OfficialStatusSeeder. Baris sumbernya dibuat sekali per badan dan jenis.
     */
    private function turunkanSumber(OfficialStatus $catatan, Authority $otoritas): void
    {
        $primer = $catatan->evidence_type?->isPrimary() !== false;
        $nama = $primer ? $otoritas->displayName() : 'Pemberitaan atas pengumuman '.$otoritas->displayName();

        $sumber = DataSource::firstOrCreate(['source_name' => $nama], [
            'source_type' => ($primer ? SourceType::OFFICIAL : SourceType::ADMIN_VERIFIED)->value,
            'source_url' => $otoritas->website,
            'source_owner' => $otoritas->name,
            'freshness_policy' => 'Dicatat kurator dari meja kurator; jadwal tinjau mengikuti jenis status.',
        ]);

        $catatan->forceFill([
            'data_source_id' => $sumber->id,
            'source' => $otoritas->displayName(),
            'published_at' => $catatan->document_date?->copy()->shiftTimezone(self::zona($catatan))->startOfDay(),
        ]);
    }

    private function arsipkan(OfficialStatus $catatan, UploadedFile $bukti): void
    {
        $arsip = $this->arsip->simpan($bukti);

        $catatan->forceFill(['evidence_archive_path' => $arsip['path'], 'evidence_sha256' => $arsip['sha256']]);
    }

    private function buangArsip(?string $path): void
    {
        if (ArsipBukti::sah($path)) {
            Storage::disk(ArsipBukti::disk())->delete($path);
        }
    }

    /**
     * Zona gunung sasaran: tanggal surat dibaca menurut zona kantornya.
     */
    private static function zona(OfficialStatus $catatan): string
    {
        $sasaran = $catatan->statusable;
        $gunung = match (true) {
            $sasaran instanceof Mountain => $sasaran,
            $sasaran instanceof Trail => $sasaran->mountain,
            $sasaran instanceof TrailSegment => $sasaran->trail?->mountain,
            default => null,
        };

        return Timezone::isSupported($gunung?->timezone) ? $gunung->timezone : Timezone::DEFAULT;
    }
}
