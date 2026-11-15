<?php

namespace App\Http\Requests\Community;

use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Support\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Catatan pendakian di luar trip (FR-25, spec v3 bagian 6.14; ruling W7-R3), untuk mencatat
 * maupun memperbaikinya. Jalurnya dari katalog terbit atau nama yang ditulis sendiri; tanggal
 * tidak di masa depan menurut zona Indonesia yang paling dahulu berganti hari; setiap angka
 * opsional dengan rentang yang sama dengan "Ubah angka" ringkasan, karena yang tidak dicatat
 * tidak diketahui, bukan nol (R-007). Pemilik dan asal baris tidak pernah dibaca dari isian.
 */
class SaveManualHikeRequest extends FormRequest
{
    public const CATATAN_MAKS = 1000;

    public function authorize(): bool
    {
        $riwayat = $this->route('riwayat');

        if ($riwayat === null) {
            return $this->user() !== null;
        }

        return $riwayat instanceof HikingHistory && $this->user()?->can('update', $riwayat) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'trail_id' => ['nullable', 'integer', Rule::exists('trails', 'id')->where('is_published', true)->whereNull('archived_at')],
            'nama_jalur' => ['required_without:trail_id', 'nullable', 'string', 'min:3', 'max:120'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:1950-01-01', 'before_or_equal:'.Timezone::latestDateInIndonesia()],
            'jenis' => ['required', Rule::enum(TripType::class)],
            'jarak_km' => ['nullable', 'numeric', 'min:0.1', 'max:200'],
            'naik_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'waktu_menit' => ['nullable', 'integer', 'min:1', 'max:7200'],
            'tertinggi_m' => ['nullable', 'integer', 'min:0', 'max:6000'],
            'catatan' => ['nullable', 'string', 'max:'.self::CATATAN_MAKS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trail_id' => 'Jalur itu tidak ada di katalog. Tulis namanya sendiri bila jalurnya belum terdaftar.',
            'nama_jalur.required_without' => 'Pilih jalur dari katalog atau tulis nama jalurnya.',
            'nama_jalur' => 'Nama jalur 3 sampai 120 karakter.',
            'tanggal.required' => 'Isi tanggal pendakian.',
            'tanggal.date_format' => 'Tanggal pendakian tidak terbaca. Pilih tanggal dari kalender.',
            'tanggal.after_or_equal' => 'Tanggal pendakian paling awal 1 Januari 1950.',
            'tanggal.before_or_equal' => 'Tanggal pendakian tidak boleh di masa depan.',
            'jenis' => 'Pilih jenis pendakian: tektok, camping, atau multi-hari.',
            'jarak_km' => 'Jarak diisi dalam kilometer, 0,1 sampai 200.',
            'naik_m' => 'Tanjakan diisi dalam meter, 0 sampai 9.000.',
            'waktu_menit' => 'Waktu bergerak diisi dalam menit, 1 sampai 7.200.',
            'tertinggi_m' => 'Titik tertinggi diisi dalam meter, 0 sampai 6.000.',
            'catatan' => 'Catatan paling banyak '.number_format(self::CATATAN_MAKS, 0, ',', '.').' karakter.',
        ];
    }

    /**
     * Kolom riwayat dari isian yang sudah divalidasi. Tanggal disimpan sebagai pukul 12.00 WIB
     * dalam UTC, supaya terbaca tanggal yang sama di WIB, WITA, maupun WIT. Nama jalur hanya
     * dipakai bila jalurnya tidak dari katalog.
     *
     * @return array<string, mixed>
     */
    public function kolom(): array
    {
        $data = $this->validated();
        $jarak = $data['jarak_km'] ?? null;
        $jalur = isset($data['trail_id']) ? Trail::query()->find($data['trail_id']) : null;

        return [
            'trail_id' => $jalur?->id,
            'trail_name' => $jalur === null ? trim((string) $data['nama_jalur']) : null,
            'trip_type' => $data['jenis'],
            'completed_at' => Carbon::parse($data['tanggal'].' 12:00:00', Timezone::DEFAULT)->utc(),
            'recorded_distance_m' => $jarak === null ? null : (int) round((float) $jarak * 1000),
            'recorded_gain_m' => $data['naik_m'] ?? null,
            'recorded_moving_minutes' => $data['waktu_menit'] ?? null,
            'recorded_highest_m' => $data['tertinggi_m'] ?? null,
            'personal_notes' => filled($data['catatan'] ?? null) ? trim((string) $data['catatan']) : null,
        ];
    }

    /**
     * Isian kosong dari formulir React datang sebagai string kosong; aturan nullable dan angka
     * membacanya sebagai nilai, bukan ketiadaan. Koma desimal Indonesia diterima untuk jarak.
     */
    protected function prepareForValidation(): void
    {
        $kosong = fn (mixed $v): mixed => is_string($v) && trim($v) === '' ? null : $v;

        $this->merge([
            'trail_id' => $kosong($this->input('trail_id')),
            'nama_jalur' => $kosong($this->input('nama_jalur')),
            'jarak_km' => is_string($jarak = $kosong($this->input('jarak_km'))) ? str_replace(',', '.', $jarak) : $jarak,
            'naik_m' => $kosong($this->input('naik_m')),
            'waktu_menit' => $kosong($this->input('waktu_menit')),
            'tertinggi_m' => $kosong($this->input('tertinggi_m')),
            'catatan' => $kosong($this->input('catatan')),
        ]);
    }
}
