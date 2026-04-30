<?php

namespace App\Channels;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Канал доставки уведомлений через Telegram.
 *
 * Заглушка — в продакшне здесь будет интеграция
 * с Telegram Bot API.
 */
class TelegramChannel implements NotificationChannelInterface
{
    /**
     * Отправить уведомление в Telegram.
     */
    public function send(Notification $notification): bool
    {
        Log::info('Telegram notification sent (stub)', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'message' => $notification->message,
        ]);

        return true;
    }
}
