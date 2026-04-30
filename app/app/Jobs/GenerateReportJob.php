<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Job асинхронной генерации отчёта.
 *
 * При успехе: статус ready, файл сохранён.
 * При ошибке: статус failed, сообщение об ошибке.
 */
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly Report $report,
    ) {}

    /**
     * Выполнить генерацию отчёта.
     */
    public function handle(ReportService $service): void
    {
        $service->generate($this->report);
    }

    /**
     * Обработка неудачной генерации.
     */
    public function failed(Throwable $exception): void
    {
        $this->report->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
