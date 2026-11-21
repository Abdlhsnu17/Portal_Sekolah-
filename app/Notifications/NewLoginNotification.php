<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $ip, private readonly string $device) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Login dari perangkat baru')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Akun Anda baru saja digunakan untuk login dari perangkat yang belum dikenal.')
            ->line('IP: '.$this->ip)
            ->line('Perangkat: '.$this->device)
            ->action('Tinjau keamanan akun', route('security.index'))
            ->line('Jika bukan Anda, segera ubah kata sandi dan keluarkan semua sesi.');
    }
}
