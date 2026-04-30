<?php

namespace App\Providers;

use App\Services\ChannelManager;
use Illuminate\Support\ServiceProvider;

/**
 * Провайдер сервиса уведомлений.
 *
 * Регистрирует ChannelManager в контейнере
 * и настраивает привязки каналов.
 */
class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов.
     */
    public function register(): void
    {
        $this->app->singleton(ChannelManager::class, function ($app) {
            return new ChannelManager($app);
        });
    }

    /**
     * Запуск сервисов.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/notification_channels.php' => config_path('notification_channels.php'),
        ], 'notification-config');
    }
}
