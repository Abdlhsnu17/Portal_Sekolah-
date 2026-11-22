<?php

namespace App\Notifications;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Registration $registration) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Konfirmasi pendaftaran '.$this->registration->nomor_pendaftaran)
            ->greeting('Halo, '.$this->registration->nama_lengkap.'!')
            ->line('Pendaftaran Anda telah kami terima.')
            ->line('Nomor pendaftaran Anda: '.$this->registration->nomor_pendaftaran)
            ->line('Simpan nomor ini untuk mengunggah dokumen dan memantau status seleksi.')
            ->action('Cek Status Pendaftaran', route('pendaftaran.status'));
    }
}
