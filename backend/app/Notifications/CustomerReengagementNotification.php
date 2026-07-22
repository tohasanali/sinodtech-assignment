<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerReengagementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Customer $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We miss you at SinodTech!')
            ->greeting("Hi {$notifiable->name},")
            ->line("It's been a while since your last purchase with us.")
            ->line('Come back and take a look at what\'s new — we\'d love to see you again.')
            ->action('Shop now', url('/'));
    }
}
