<?php

namespace App\Services\Trips;

use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Support\Carbon;

/**
 * Pesan rencana perjalanan untuk kontak yang tidak ikut mendaki (spec v3 bagian 10.8; rencana
 * induk W6 butir 8; ruling W6-R4). Teks biasa yang dikirim pendaki sendiri lewat WhatsApp atau
 * SMS, dengan instruksi eskalasi: bila belum ada kabar sampai jam kembali ditambah satu jam,
 * hubungi basecamp atau pengelola resmi gunung, lalu Basarnas 115 atau 112. Pesan menyatakan
 * bahwa Memount tidak memantau perjalanan; ini bukan berbagi lokasi langsung.
 *
 * Tanpa jam kembali pesannya tidak dibuat: kontak tidak tahu kapan harus bertindak, dan jam
 * yang ditebak lebih berbahaya daripada tidak ada (R-007). Jam kembali yang tidak sesudah mulai
 * trip juga tidak menghasilkan pesan (pra-tinjau PT7-7a): instruksi eskalasi yang jatuh sebelum
 * pendakian dimulai membuat kontak menelepon basecamp atau Basarnas tanpa alasan. Mengubah
 * tanggal atau jam mulai lewat formulir rencana mengosongkan jam kembali (ruling W6-7b,
 * TripLifecycle::update); halaman sesudah perubahan itu memakai kalimat MULAI_BERUBAH.
 *
 * Kontak hanya diminta menyebut isian yang memang ada di pesan (PT7-9), dan pengelola disebut
 * "pengelola resmi di gunung ini" karena kontaknya tingkat gunung (ruling W6-7a).
 */
final class PesanRencana
{
    /** Tenggang sebelum kontak bertindak, sesudah jam kembali (spec v3 bagian 10.8). */
    public const TENGGANG_JAM = 1;

    /** Kalimat sesudah jam kembali dikosongkan karena mulai trip berpindah (ruling W6-7b). */
    public const MULAI_BERUBAH = 'Tanggal atau jam mulai trip berubah. Isi jam kembali lagi supaya kontak Anda tahu kapan harus bertindak.';

    public function __construct(private readonly KartuDarurat $darurat) {}

    /**
     * @param  bool  $mulaiBaruBerubah  jam kembali baru saja dikosongkan TripLifecycle::update karena tanggal atau jam mulai berpindah
     * @return array{pesan: string|null, kurang: string|null, kembali: array{tanggal: string|null, jam: string|null}, registrasi: string|null, zona: string, urls: array{simpan: string, cetak: string}}
     */
    public function untuk(TripPlan $trip, User $pengguna, bool $mulaiBaruBerubah = false): array
    {
        $trip->loadMissing('trail.mountain.authorities');
        $zona = Timezone::forTrail($trip->trail);
        $kembali = $trip->expected_return_at?->copy()->setTimezone($zona);
        $basi = $this->kembaliTidakSesudahMulai($trip, $zona);

        return [
            'pesan' => $kembali === null || $basi ? null : $this->teks($trip, $pengguna, $kembali, $zona),
            'kurang' => match (true) {
                $kembali === null && $mulaiBaruBerubah => self::MULAI_BERUBAH,
                $kembali === null => 'Isi perkiraan jam kembali dulu. Tanpa itu, kontak Anda tidak tahu kapan harus bertindak.',
                $basi => 'Jam kembali yang tersimpan tidak sesudah mulai trip. Perbarui jam kembali supaya kontak Anda tahu kapan harus bertindak.',
                default => null,
            },
            'kembali' => ['tanggal' => $kembali?->toDateString(), 'jam' => $kembali?->format('H:i')],
            'registrasi' => $trip->registration_number,
            'zona' => Timezone::label($zona),
            'urls' => [
                'simpan' => route('trips.return.update', $trip),
                'cetak' => route('trips.print', $trip),
            ],
        ];
    }

    /**
     * Jam kembali yang tidak sesudah mulai trip (planned_date ditambah start_time, atau 00.00,
     * di zona gunung): tersisa dari tanggal trip yang diubah di luar formulir rencana, atau
     * diisi tidak sesudah jam mulai. Satu predikat untuk pesan dan versi cetak (PT7-7a).
     */
    public function kembaliTidakSesudahMulai(TripPlan $trip, ?string $zona = null): bool
    {
        if ($trip->expected_return_at === null) {
            return false;
        }

        $zona ??= Timezone::forTrail($trip->trail);
        $mulai = Carbon::parse($trip->planned_date->toDateString().' '.($trip->start_time ? substr((string) $trip->start_time, 0, 5) : '00:00'), $zona);

        return $trip->expected_return_at->lessThanOrEqualTo($mulai);
    }

    private function teks(TripPlan $trip, User $pengguna, Carbon $kembali, string $zona): string
    {
        $jalur = $trip->trail;
        $label = Timezone::label($zona);
        $waktu = fn (Carbon $t) => $t->translatedFormat('l, j F Y').' pukul '.$t->format('H.i').' '.$label;
        $jamMulai = $trip->start_time ? ' pukul '.str_replace(':', '.', substr((string) $trip->start_time, 0, 5)).' '.$label : '';
        $pengelola = $this->darurat->untuk($jalur)['pengelola'];

        $baris = array_filter([
            'Rencana pendakian '.$pengguna->name,
            'Jalur: '.$jalur->name.', '.implode(', ', array_filter([$jalur->mountain?->name, $jalur->mountain?->province])),
            'Mulai: '.$trip->planned_date->translatedFormat('l, j F Y').$jamMulai,
            $trip->participants_count ? 'Peserta: '.$trip->participants_count.' orang' : null,
            $trip->registration_number ? 'Nomor registrasi: '.$trip->registration_number : null,
            'Perkiraan kembali: '.$waktu($kembali),
        ]);

        return implode("\n", $baris)
            ."\n\n".'Bila belum ada kabar dari saya sampai '.$waktu($kembali->copy()->addHours(self::TENGGANG_JAM)).', hubungi '
            .($pengelola === null ? 'basecamp jalur ini' : 'pengelola resmi di gunung ini, '.$pengelola['nama'].', di '.$pengelola['kontak'])
            .', lalu Basarnas 115 atau 112. Sebutkan '.self::sebutkan($trip).'.'
            ."\n\n".'Memount tidak memantau perjalanan ini dan tidak membagikan lokasi saya.';
    }

    /**
     * Isian yang diminta disebut kontak, hanya yang memang ada di pesan (PT7-9): "a dan b",
     * "a, b, dan c", atau "a, b, c, dan d".
     */
    private static function sebutkan(TripPlan $trip): string
    {
        $disebut = array_values(array_filter([
            'nama jalur',
            'tanggal mulai',
            $trip->participants_count ? 'jumlah peserta' : null,
            $trip->registration_number ? 'nomor registrasi' : null,
        ]));
        $terakhir = array_pop($disebut);

        return count($disebut) === 1 ? $disebut[0].' dan '.$terakhir : implode(', ', $disebut).', dan '.$terakhir;
    }
}
