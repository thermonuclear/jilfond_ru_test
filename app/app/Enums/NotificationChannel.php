<?php

namespace App\Enums;

/**
 * Каналы доставки уведомлений.
 */
enum NotificationChannel: string
{
    /** Электронная почта */
    case Email = 'email';

    /** Telegram */
    case Telegram = 'telegram';
}
