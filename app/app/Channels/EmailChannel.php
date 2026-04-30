<?php

namespace App\Channels;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Канал доставки уведомлений через электронную почту.
 *
 * Заглушка — в продакшне здесь будет интеграция
 * с почтовым провайдером (Mailgun, SendGrid и т.д.).
 */
class EmailChannel implements NotificationChannelInterface
{
    /**
     * Отправить уведомление по email.
     */
    public function send(Notification $notification): bool
    {
        Log::info('Email notification sent (stub)', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'message' => $notification->message,
        ]);

        return true;
    }
}
