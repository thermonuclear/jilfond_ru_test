<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Job отправки уведомления.
 *
 * Гарантирует доставку через механизм повторных попыток:
 * - 3 попытки с экспоненциальной задержкой (30, 60, 120 сек)
 * - При успехе: статус меняется на «sent»
 * - При неудаче после всех попыток: статус «failed»
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Максимальное количество попыток.
     */
    public int $tries = 3;

    /**
     * Максимальное время выполнения (секунды).
     */
    public int $timeout = 30;

    /**
     * Уведомление для отправки.
     */
    public function __construct(
        public readonly Notification $notification,
    ) {}

    /**
     * Задержка между попытками (секунды).
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    /**
     * Выполнить отправку уведомления.
     */
    public function handle(NotificationService $service): void
    {
        $service->send($this->notification);
        $service->markAsSent($this->notification);
    }

    /**
     * Обработка неудачной попытки.
     */
    public function failed(Throwable $exception): void
    {
        $service = app(NotificationService::class);
        $service->markAsFailed($this->notification, $exception->getMessage());
    }
}
