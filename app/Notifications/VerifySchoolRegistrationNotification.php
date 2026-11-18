<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifySchoolRegistrationNotification extends Notification
{
    use Queueable;

    public function __construct(public Tenant $tenant) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'school.verify',
            now()->addHours(24),
            [
                'user' => $notifiable->getKey(),
                'tenant' => $this->tenant->getKey(),
            ],
        );

        return (new MailMessage)
            ->subject("Verifikasi Pendaftaran Sekolah: {$this->tenant->name}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Terima kasih telah mendaftarkan **{$this->tenant->name}** di aplikasi E-SPP.")
            ->line('Silakan klik tombol di bawah ini untuk memverifikasi email dan mengaktifkan akun sekolah Anda:')
            ->action('Verifikasi & Aktifkan Sekolah', $verificationUrl)
            ->line('Link ini berlaku selama 24 jam.');
    }
}
