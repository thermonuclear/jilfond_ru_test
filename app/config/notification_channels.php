<?php

use App\Channels\EmailChannel;
use App\Channels\TelegramChannel;

/**
 * Конфигурация каналов доставки уведомлений.
 *
 * Для добавления нового канала:
 * 1. Создать класс, реализующий NotificationChannelInterface
 * 2. Добавить запись в этот конфиг
 *
 * Изменения в существующем коде НЕ требуются.
 */
return [
    'channels' => [
        'email' => EmailChannel::class,
        'telegram' => TelegramChannel::class,
    ],
];
