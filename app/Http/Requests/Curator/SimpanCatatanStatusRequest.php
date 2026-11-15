<?php

namespace App\Http\Requests\Curator;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\DataResmi\ArsipBukti;
use App\Services\DataResmi\Yurisdiksi;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Isian langkah 1 dan 2 meja kurator: masukan (bukti) dan penstrukturan (spec v3 bagian 10.7;
 * ruling W8-R15). Sasaran dan penerbit dipilih dari daftar yang dikirim server, lalu dicocokkan
 * ulang dengan yurisdiksi pengguna sesi; pencatat, keadaan, dan pemeriksa tidak pernah diterima
 * dari isian (layanan TerbitStatus menulisnya). Tanggal surat, mulai berlaku, dan berakhir
 * dibaca menurut zona gunung sasarannya.
 */
class SimpanCatatanStatusRequest extends FormRequest
{
    private ?Model $sasaranTerpilih = null;

    public function authorize(): bool
    {
        $catatan = $this->route('status');

        return $catatan instanceof OfficialStatus
            ? $this->user()?->can('updateDraft', $catatan) ?? false
            : $this->user()?->can('viewDesk', OfficialStatus::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $baru = ! $this->route('status') instanceof OfficialStatus;

        return [
            'sasaran' => [$baru ? 'required' : 'prohibited', 'string', 'regex:/^(MOUNTAIN|TRAIL|SEGMENT):[0-9]{1,18}$/'],
            'otoritas' => [$baru ? 'required' : 'prohibited', 'integer'],
            'status' => ['required', new Enum(OfficialStatusValue::class)],
            'jenis_bukti' => ['required', new Enum(EvidenceType::class)],
            'url_sumber' => ['nullable', 'url:https,http', 'max:500'],
            'waktu_tangkap' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'bukti' => ['nullable', ...ArsipBukti::aturan()],
            'nomor_surat' => ['nullable', 'string', 'max:120'],
            'tanggal_surat' => ['nullable', 'date_format:Y-m-d'],
            'mulai_berlaku' => ['required', 'date_format:Y-m-d'],
            'cara_berakhir' => ['required', new Enum(StatusEndCondition::class)],
            'berlaku_sampai' => ['nullable', 'required_if:cara_berakhir,'.StatusEndCondition::ON_DATE->value, 'prohibited_unless:cara_berakhir,'.StatusEndCondition::ON_DATE->value, 'date_format:Y-m-d', 'after_or_equal:mulai_berlaku'],
            'batas' => ['nullable', 'string', 'max:200'],
            'golongan_alasan' => ['nullable', new Enum(StatusReasonCategory::class)],
            'alasan' => ['nullable', 'string', 'max:500'],
            'dasar_hukum' => ['nullable', 'string', 'max:255'],
            'kutipan' => ['nullable', 'string', 'max:1000'],
            'ringkasan' => ['required', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sasaran.required' => 'Pilih gunung, jalur, atau segmen yang dikenai status ini.',
            'sasaran.regex' => 'Pilih sasaran dari daftar.',
            'otoritas.required' => 'Pilih badan yang menerbitkan status ini.',
            'mulai_berlaku.required' => 'Tulis tanggal status ini mulai berlaku menurut pengelola.',
            'berlaku_sampai.required_if' => 'Tulis tanggal berakhir yang disebut pengelola.',
            'berlaku_sampai.prohibited_unless' => 'Tanggal berakhir hanya diisi bila pengelola menyebut tanggalnya.',
            'berlaku_sampai.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai berlaku.',
            'ringkasan.required' => 'Tulis ringkasan satu kalimat dalam bahasa sederhana untuk pendaki.',
        ];
    }

    /**
     * Sasaran harus ada dan berada di kawasan badan pengguna; penerbit harus salah satu badan
     * itu. Galat ditulis pada isiannya, bukan halaman 404 yang menghapus isian lain.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $catatan = $this->route('status');
            $sasaran = $catatan instanceof OfficialStatus ? $catatan->statusable : $this->sasaran();
            $tangkap = $this->validated('waktu_tangkap');

            // Waktu tangkap ditulis menurut jam gunung, jadi dibandingkan di zona itu, bukan UTC.
            if ($sasaran !== null && $tangkap !== null && Carbon::parse($tangkap, $this->zona($sasaran))->isFuture()) {
                $validator->errors()->add('waktu_tangkap', 'Waktu tangkap tidak boleh di masa depan.');
            }

            if ($catatan instanceof OfficialStatus) {
                return;
            }

            if ($sasaran === null) {
                $validator->errors()->add('sasaran', 'Sasaran itu tidak ditemukan. Pilih dari daftar.');

                return;
            }

            $badan = app(Yurisdiksi::class)->otoritasUntuk($this->user(), $sasaran);

            if ($badan->isEmpty()) {
                $validator->errors()->add('sasaran', 'Sasaran ini di luar kawasan badan Anda.');
            } elseif (! $badan->contains('id', (int) $this->validated('otoritas'))) {
                $validator->errors()->add('otoritas', 'Pilih badan yang berwenang atas sasaran ini dan tempat Anda menjadi anggota.');
            }
        }];
    }

    public function sasaran(): ?Model
    {
        if ($this->sasaranTerpilih !== null) {
            return $this->sasaranTerpilih;
        }

        [$jenis, $id] = explode(':', (string) $this->input('sasaran')) + [null, null];

        return $this->sasaranTerpilih = match ($jenis) {
            'MOUNTAIN' => Mountain::find($id),
            'TRAIL' => Trail::find($id),
            'SEGMENT' => TrailSegment::find($id),
            default => null,
        };
    }

    public function otoritas(): Authority
    {
        return Authority::findOrFail((int) $this->validated('otoritas'));
    }

    /**
     * Kolom isi catatan untuk TerbitStatus, dengan tanggal yang dibaca menurut zona gunung.
     *
     * @return array<string, mixed>
     */
    public function kolom(Model $sasaran): array
    {
        $data = $this->validated();
        $zona = $this->zona($sasaran);

        return [
            'status' => $data['status'],
            'evidence_type' => $data['jenis_bukti'],
            'source_url' => $data['url_sumber'] ?? null,
            'captured_at' => isset($data['waktu_tangkap']) ? Carbon::parse($data['waktu_tangkap'], $zona)->utc() : now(),
            'document_number' => $data['nomor_surat'] ?? null,
            'document_date' => $data['tanggal_surat'] ?? null,
            'effective_at' => Carbon::parse($data['mulai_berlaku'], $zona)->startOfDay()->utc(),
            'end_condition' => $data['cara_berakhir'],
            'expires_at' => isset($data['berlaku_sampai']) ? Carbon::parse($data['berlaku_sampai'], $zona)->endOfDay()->utc() : null,
            'limit_description' => $data['batas'] ?? null,
            'reason_category' => $data['golongan_alasan'] ?? null,
            'reason' => $data['alasan'] ?? null,
            'legal_basis' => $data['dasar_hukum'] ?? null,
            'official_excerpt' => $data['kutipan'] ?? null,
            'plain_summary' => $data['ringkasan'],
        ];
    }

    private function zona(Model $sasaran): string
    {
        $gunung = match (true) {
            $sasaran instanceof Mountain => $sasaran,
            $sasaran instanceof Trail => $sasaran->mountain,
            $sasaran instanceof TrailSegment => $sasaran->trail?->mountain,
            default => null,
        };

        return Timezone::isSupported($gunung?->timezone) ? $gunung->timezone : Timezone::DEFAULT;
    }
}
