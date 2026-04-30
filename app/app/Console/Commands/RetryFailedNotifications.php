<?php

namespace App\Console\Commands;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Console\Command;

/**
 * Команда повторной отправки failed уведомлений.
 *
 * Берёт уведомления со статусом «failed», которые:
 * - имеют меньше 10 попыток
 * - созданы не ранее 24 часов назад
 *
 * и ставит их обратно в очередь.
 */
class RetryFailedNotifications extends Command
{
    protected $signature = 'notifications:retry-failed
                            {--limit=50 : Максимальное количество уведомлений для повторной отправки}';

    protected $description = 'Повторно поставить failed уведомления в очередь';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $failedNotifications = Notification::query()
            ->where('status', NotificationStatus::Failed)
            ->where('attempts', '<', 10)
            ->where('created_at', '>', now()->subHours(24))
            ->limit($limit)
            ->get();

        if ($failedNotifications->isEmpty()) {
            $this->info('Нет failed уведомлений для повторной отправки.');

            return self::SUCCESS;
        }

        $this->info("Найдено {$failedNotifications->count()} уведомлений для повторной отправки.");

        $bar = $this->output->createProgressBar($failedNotifications->count());
        $bar->start();

        foreach ($failedNotifications as $notification) {
            SendNotificationJob::dispatch($notification);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Готово! Уведомления поставлены в очередь.');

        return self::SUCCESS;
    }
}
