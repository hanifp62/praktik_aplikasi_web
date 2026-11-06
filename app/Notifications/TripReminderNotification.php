<?php

namespace App\Notifications;

use App\Models\TripPlan;
use App\Support\Timezone;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Surel pengingat perjalanan (FR-24).
 *
 * Isinya hanya yang dibutuhkan untuk bersiap: nama jalur, tanggal dengan penanda zona
 * gunungnya, dan tautan ke cek kesiapan. Catatan, peserta, nama, dan email pemilik
 * tidak ikut: surel melewati server pihak lain dan dapat dibaca di perangkat lain.
 *
 * Sistem tidak menyatakan apa pun aman (R-012, R-014). Yang diingatkan adalah
 * memeriksa keadaan terbaru dan memastikannya kepada pengelola kawasan.
 *
 * Tidak memakai antrean: dikirim dari perintah terjadwal yang sudah berjalan di latar,
 * dan hasil kirimnya menentukan apakah pengingat ditandai terkirim.
 */
class TripReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TripPlan $trip,
        public readonly int $sisaHari,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $jalur = $this->trip->trail;
        $zona = Timezone::label(Timezone::forTrail($jalur));
        $tanggal = $this->trip->planned_date->translatedFormat('l, d F Y');

        $waktu = $this->trip->start_time
            ? sprintf('%s, mulai pukul %s %s', $tanggal, Carbon::parse($this->trip->start_time)->format('H.i'), $zona)
            : sprintf('%s (%s)', $tanggal, $zona);

        $kapan = $this->sisaHari <= 1 ? 'besok' : $this->sisaHari.' hari lagi';

        return (new MailMessage)
            ->subject(sprintf('Pengingat: pendakian %s %s', $jalur->name, $kapan))
            ->greeting('Halo,')
            ->line(sprintf('Rencana pendakian Anda di %s, %s, jatuh pada %s.', $jalur->name, $jalur->mountain->name, $waktu))
            ->line('Sebelum berangkat, buka cek kesiapan. Status resmi jalur, prakiraan cuaca area sekitar jalur, dan butir persiapan yang belum dikonfirmasi dapat berubah sejak Anda merencanakan.')
            ->action('Buka cek kesiapan', route('trips.readiness', $this->trip))
            ->line('Status resmi adalah keterangan operasional dari pengelola, bukan jaminan kondisi di jalur. Pastikan kembali kepada pengelola kawasan sebelum berangkat.')
            ->line('Anda menerima surel ini karena menyalakan pengingat untuk rencana ini. Pengingat dapat dimatikan di halaman trip.')
            ->salutation('Salam, '.config('app.name'));
    }
}
