<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class EmployeeSetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Aktivasi Akun SADAR-PRI')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Akun SADAR-PRI Anda telah dibuat oleh HR.')
            ->line('Silakan buat password Anda sendiri melalui tombol di bawah ini.')
            ->action('Buat Password', $url)
            ->line('Link ini akan kedaluwarsa dalam 60 menit.')
            ->line('Jika Anda merasa tidak seharusnya menerima email ini, abaikan email ini.');
    }
}
