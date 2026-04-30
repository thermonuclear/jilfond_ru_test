<?php

namespace App\Services;

use App\Channels\NotificationChannelInterface;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * Менеджер каналов доставки уведомлений.
 *
 * Расширяет Laravel Manager для поддержки OCP:
 * новый канал добавляется через extend() без изменения этого класса.
 *
 * Использование:
 *   $manager->driver('email')->send($notification);
 *   $manager->extend('sms', fn($app) => new SmsChannel());
 */
class ChannelManager extends Manager
{
    /**
     * Получить канал по умолчанию.
     */
    public function getDefaultDriver(): string
    {
        return 'email';
    }

    /**
     * Создать экземпляр email-канала.
     */
    public function createEmailDriver(): NotificationChannelInterface
    {
        return $this->container->make(
            config('notification_channels.channels.email')
        );
    }

    /**
     * Создать экземпляр telegram-канала.
     */
    public function createTelegramDriver(): NotificationChannelInterface
    {
        return $this->container->make(
            config('notification_channels.channels.telegram')
        );
    }

    /**
     * Переопределение для поддержки FQCN из конфига.
     *
     * Если метод create{Driver}Driver не найден, пытается
     * создать экземпляр по полному имени класса.
     */
    protected function createDriver($driver): NotificationChannelInterface
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $e) {
            $class = config("notification_channels.channels.{$driver}");

            if ($class !== null && class_exists($class)) {
                return $this->container->make($class);
            }

            throw new InvalidArgumentException("Notification channel [{$driver}] is not supported.");
        }
    }
}
