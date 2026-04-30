<?php

namespace App\Console\Commands;

use App\Jobs\GenerateReportJob;
use App\Models\Report;
use Illuminate\Console\Command;

/**
 * Команда повторной генерации failed отчётов.
 *
 * Берёт отчёты со статусом «failed», которые:
 * - имеют меньше 5 попыток генерации
 * - созданы не ранее 24 часов назад
 *
 * и ставит их обратно в очередь.
 */
class RetryFailedReports extends Command
{
    protected $signature = 'reports:retry-failed
                            {--limit=50 : Максимальное количество отчётов для повторной генерации}';

    protected $description = 'Повторно поставить failed отчёты в очередь';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $failedReports = Report::query()
            ->where('status', 'failed')
            ->where('updated_at', '>', now()->subHours(24))
            ->limit($limit)
            ->get();

        if ($failedReports->isEmpty()) {
            $this->info('Нет failed отчётов для повторной генерации.');

            return self::SUCCESS;
        }

        $this->info("Найдено {$failedReports->count()} отчётов для повторной генерации.");

        $bar = $this->output->createProgressBar($failedReports->count());
        $bar->start();

        foreach ($failedReports as $report) {
            GenerateReportJob::dispatch($report);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Готово! Отчёты поставлены в очередь.');

        return self::SUCCESS;
    }
}
