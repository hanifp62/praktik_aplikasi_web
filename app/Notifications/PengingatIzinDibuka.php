<?php

namespace App\Notifications;

use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Services\PermitService;
use App\Support\Timezone;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Surel hari pemesanan izin dibuka (FR-24 diperluas, GR-06; aksi Beranda "Ingatkan saat
 * dibuka", spec v3 bagian 6.1).
 *
 * Isinya hanya yang dibutuhkan untuk memesan: jalur, tanggal rencana dengan penanda zona
 * gunungnya, pengelola, dan tautan sistem resmi bila tercatat. Memount menautkan sistem
 * resmi dan tidak menjual izin, dan kuota tersisa hanya ada di sistem resmi (§43), jadi surel
 * ini tidak menyebut kuota. Catatan, peserta, nama, dan email pemilik tidak ikut, sama dengan
 * TripReminderNotification.
 *
 * Tidak memakai antrean: dikirim dari perintah terjadwal yang sudah berjalan di latar, dan
 * hasil kirimnya menentukan apakah pengingat ditandai terkirim.
 *
 * Ruling W5-G1b (R-007): klaim "menautkan" dan tombol "Buka sistem resmi" hanya ditulis ketika
 * PermitService::tautanPeriksa() sungguh mengembalikan tautan (berbasis filled(), bukan truthy
 * $aturan->booking_url mentah). Tanpa aturan, tanpa tautan, atau alamat berisi spasi saja,
 * tombolnya "Buka rencana trip" dan kalimatnya "Memount tidak memesan atau menjual izin.",
 * dari sumber yang sama dengan Beranda dan Rencanakan (PermitService::kalimatTautan()).
 */
class PengingatIzinDibuka extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TripPlan $trip,
        public readonly ?PermitRequirement $aturan,
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
        $tempat = $this->aturan?->authority ? ' di sistem resmi '.$this->aturan->authority : '';
        $tautanResmi = $this->aturan !== null ? PermitService::tautanPeriksa($this->aturan) : null;

        $surel = (new MailMessage)
            ->subject(sprintf('Pemesanan izin %s dibuka hari ini', $jalur->name))
            ->greeting('Halo,')
            ->line(sprintf('Pemesanan izin pendakian untuk rencana Anda di %s, %s, tanggal %s (%s), dibuka hari ini%s.', $jalur->name, $jalur->mountain->name, $tanggal, $zona, $tempat))
            ->line(PermitService::kalimatTautan($tautanResmi !== null).' Kuota dan ketentuan terbaru hanya ada di sistem resmi.');

        $surel = $tautanResmi !== null
            ? $surel->action('Buka sistem resmi', $tautanResmi['href'])
            : $surel->action('Buka rencana trip', route('trips.show', $this->trip));

        return $surel
            ->line('Anda menerima surel ini karena menyalakan pengingat untuk rencana ini. Pengingat dapat dimatikan di halaman trip.')
            ->salutation('Salam, '.config('app.name'));
    }
}
