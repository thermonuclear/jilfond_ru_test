<?php

namespace App\Enums;

/**
 * Статусы доставки уведомлений.
 */
enum NotificationStatus: string
{
    /** Уведомление создано и ожидает отправки */
    case Pending = 'pending';

    /** Уведомление успешно доставлено */
    case Sent = 'sent';

    /** Ошибка доставки после повторных попыток */
    case Failed = 'failed';
}
