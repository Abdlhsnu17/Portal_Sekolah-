<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $event, private readonly string $ip) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Perubahan keamanan akun')
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->event)
            ->line('Alamat IP: '.$this->ip)
            ->action('Tinjau keamanan akun', route('security.index'))
            ->line('Jika bukan Anda, segera hubungi administrator sekolah.');
    }
}
