<?php

namespace App\Services\DataResmi;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusVerificationState;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\OfficialStatusService;
use App\Support\Timezone;

/**
 * Pemeriksaan konsistensi otomatis sebuah catatan meja kurator (spec v3 bagian 10.7; rencana
 * induk W8 butir 4; ruling W8-R12).
 *
 * Tingkat "blokir" ditegakkan TerbitStatus saat kirim dan verifikasi; tingkat "peringatan" hanya
 * tampil di meja kurator. Pemeriksaan kawasan (jalur Buka di kawasan yang ditutup) sengaja hanya
 * peringatan: kebijakan tingkat kawasan terhadap rekomendasi adalah keputusan pemilik yang belum
 * diambil (Q12), jadi status jalur dan rekomendasi pendaki tidak diubah di sini.
 */
final class PemeriksaanKonsistensi
{
    public function __construct(
        private readonly ArsipBukti $arsip,
        private readonly OfficialStatusService $status,
        private readonly ArahPerubahan $arah,
    ) {}

    /**
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    public function periksa(OfficialStatus $catatan): array
    {
        return [
            ...$this->tanggal($catatan),
            ...$this->wewenang($catatan),
            ...$this->bukti($catatan),
            ...$this->bertentangan($catatan),
            ...$this->kawasan($catatan),
        ];
    }

    /**
     * @return array<int, string> pesan temuan tingkat blokir
     */
    public function blokir(OfficialStatus $catatan): array
    {
        return array_values(array_map(
            fn (array $t) => $t['pesan'],
            array_filter($this->periksa($catatan), fn (array $t) => $t['tingkat'] === 'blokir'),
        ));
    }

    /**
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function tanggal(OfficialStatus $catatan): array
    {
        $temuan = [];

        if ($catatan->expires_at !== null && $catatan->effective_at !== null && $catatan->expires_at->lessThanOrEqualTo($catatan->effective_at)) {
            $temuan[] = self::temuanBlokir('tanggal_terbalik', 'Tanggal berakhir tidak boleh sebelum tanggal mulai berlaku.');
        }

        if ($catatan->end_condition === StatusEndCondition::ON_DATE && $catatan->expires_at === null) {
            $temuan[] = self::temuanBlokir('akhir_tanpa_tanggal', 'Cara berakhir "sampai tanggal tertentu" butuh tanggal berakhir.');
        }

        if ($catatan->end_condition !== null && $catatan->end_condition !== StatusEndCondition::ON_DATE && $catatan->expires_at !== null) {
            $temuan[] = self::temuanBlokir('tanggal_tanpa_akhir', 'Tanggal berakhir hanya diisi bila pengelola menyebut tanggalnya. Pilih "sampai tanggal tertentu" atau kosongkan tanggalnya.');
        }

        return $temuan;
    }

    /**
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function wewenang(OfficialStatus $catatan): array
    {
        $sasaran = $catatan->statusable;
        $badan = $catatan->authority;

        if ($sasaran === null || $badan === null || ! $badan->type->mayDeclareTrailStatus()) {
            return [self::temuanBlokir('di_luar_yurisdiksi', 'Badan penerbit tidak berwenang menyatakan status untuk sasaran ini.')];
        }

        $gunung = Yurisdiksi::gunungSasaran($sasaran);

        if ($gunung === null || ! $badan->mountains()->whereKey($gunung)->exists()) {
            return [self::temuanBlokir('di_luar_yurisdiksi', 'Sasaran ini di luar kawasan '.$badan->displayName().'.')];
        }

        return [];
    }

    /**
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function bukti(OfficialStatus $catatan): array
    {
        $temuan = [];

        if ($catatan->evidence_archive_path === null) {
            $temuan[] = self::temuanBlokir('tanpa_arsip', 'Unggah salinan bukti: surat, laman, atau unggahan akun resmi pengelola.');
        } elseif (! $this->arsip->utuh($catatan)) {
            $temuan[] = self::temuanBlokir('arsip_berubah', 'Arsip bukti tidak lagi cocok dengan sidik SHA-256 yang dicatat. Unggah ulang salinannya.');
        }

        if ($catatan->evidence_type === EvidenceType::NEWS) {
            $temuan[] = $this->arah->membuka($catatan)
                ? self::temuanBlokir('berita_membuka', 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.')
                : self::temuanPeringatan('bukti_berita', 'Bukti hanya berita: catatan ini tidak terbit sebelum diperiksa kurator kedua, dan dikutip sebagai terverifikasi admin, bukan Resmi.');
        }

        return $temuan;
    }

    /**
     * Catatan lain untuk sasaran yang sama yang tidak akan digantikan catatan ini: catatan terlihat
     * yang baru mulai sesudahnya, dan catatan kurator lain yang menunggu verifikasi atau
     * dipersoalkan, dengan nilai berbeda.
     *
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function bertentangan(OfficialStatus $catatan): array
    {
        $mulai = $catatan->effective_at ?? now();

        $lain = OfficialStatus::query()
            ->where('statusable_type', $catatan->statusable_type)
            ->where('statusable_id', $catatan->statusable_id)
            ->whereKeyNot($catatan->id)
            ->whereNull('superseded_by_id')
            ->where('status', '!=', $catatan->status->value)
            ->where(fn ($q) => $q
                ->whereIn('verification_state', [StatusVerificationState::PENDING->value, StatusVerificationState::DISPUTED->value])
                ->orWhere(fn ($terlihat) => $terlihat->terlihatPendaki()->where('effective_at', '>', $mulai)))
            ->orderBy('id')
            ->get();

        return $lain->map(function (OfficialStatus $s) use ($catatan) {
            $tertunda = in_array($s->verification_state, [StatusVerificationState::PENDING, StatusVerificationState::DISPUTED], true) && ! $s->terlihatPendaki();

            return self::temuanPeringatan('bertentangan', $tertunda
                ? 'Catatan #'.$s->id.' ('.$s->status->label().') untuk sasaran yang sama '.$s->verification_state->label().' dan belum terbaca pendaki; menunggu verifikasi kedua. Pastikan keduanya tidak bertentangan.'
                : 'Catatan #'.$s->id.' ('.$s->status->label().' mulai '.$s->effective_at?->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M Y').') berlaku sesudah catatan ini dan akan menentukan status mulai tanggalnya. Catatan '.$catatan->status->label().' ini tidak menggantikannya.');
        })->values()->all();
    }

    /**
     * Q12: jalur atau segmen Buka di gunung yang sedang Tutup atau Dibatasi. Kaskade §42 tetap
     * menampilkan status gunung kepada pendaki; meja kurator hanya diberi tahu.
     *
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function kawasan(OfficialStatus $catatan): array
    {
        $sasaran = $catatan->statusable;

        if ($catatan->status !== OfficialStatusValue::OPEN || $sasaran instanceof Mountain || $sasaran === null) {
            return [];
        }

        $gunung = match (true) {
            $sasaran instanceof Trail => $sasaran->mountain,
            $sasaran instanceof TrailSegment => $sasaran->trail?->mountain,
            default => null,
        };

        if (! $gunung instanceof Mountain) {
            return [];
        }

        $nilai = $this->status->statusOnDate($gunung, $catatan->effective_at ?? now());

        if (! in_array($nilai, [OfficialStatusValue::CLOSED, OfficialStatusValue::RESTRICTED], true)) {
            return [];
        }

        return [self::temuanPeringatan('kawasan_tertutup', $gunung->name.' sedang '.$nilai->label().' di tingkat gunung. Pendaki tetap melihat status gunung itu untuk jalur ini (pembatasan turun ke jalur). Kebijakan kawasan terhadap rekomendasi menunggu keputusan pemilik (Q12).')];
    }

    /**
     * @return array{kode: string, tingkat: 'blokir', pesan: string}
     */
    private static function temuanBlokir(string $kode, string $pesan): array
    {
        return ['kode' => $kode, 'tingkat' => 'blokir', 'pesan' => $pesan];
    }

    /**
     * @return array{kode: string, tingkat: 'peringatan', pesan: string}
     */
    private static function temuanPeringatan(string $kode, string $pesan): array
    {
        return ['kode' => $kode, 'tingkat' => 'peringatan', 'pesan' => $pesan];
    }
}
