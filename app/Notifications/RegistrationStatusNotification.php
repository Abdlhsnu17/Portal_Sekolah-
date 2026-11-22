<?php

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Registration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $labels = ['menunggu' => 'Menunggu Verifikasi', 'diverifikasi' => 'Terverifikasi', 'diterima' => 'Diterima', 'ditolak' => 'Tidak Diterima'];
        $status = $labels[$this->registration->status] ?? $this->registration->status;

        $mail = (new MailMessage)
            ->subject('Pembaruan status pendaftaran '.$this->registration->nomor_pendaftaran)
            ->greeting('Halo, '.$this->registration->nama_lengkap.'!')
            ->line('Status pendaftaran Anda telah diperbarui menjadi: '.$status.'.')
            ->line('Nomor pendaftaran: '.$this->registration->nomor_pendaftaran);

        if (filled($this->registration->catatan)) {
            $mail->line('Catatan panitia: '.$this->registration->catatan);
        }

        return $mail->action('Cek Status Pendaftaran', route('pendaftaran.status'))
            ->line('Terima kasih telah mendaftar.');
    }
}
