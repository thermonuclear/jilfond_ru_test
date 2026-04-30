<?php

namespace App\Channels;

use App\Models\Notification;

/**
 * Контракт канала доставки уведомлений.
 *
 * Каждый канал (email, telegram и т.д.) должен реализовывать
 * этот интерфейс для обеспечения единообразной отправки.
 */
interface NotificationChannelInterface
{
    /**
     * Отправить уведомление через данный канал.
     *
     * @return true если отправка успешна
     *
     * @throws \Throwable при ошибке доставки
     */
    public function send(Notification $notification): bool;
}
