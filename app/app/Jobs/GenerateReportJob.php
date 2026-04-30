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
 * При ошибке: статус failed, orphan-файл удалён.
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
     *
     * Удаляет orphan-файл, если он был создан до ошибки,
     * и помечает отчёт как failed.
     */
    public function failed(Throwable $exception): void
    {
        $service = app(ReportService::class);
        $service->deleteFile($this->report->file_path);

        $this->report->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'file_path' => null,
        ]);
    }
}
