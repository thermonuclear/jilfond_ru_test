<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Контроллер отчётов.
 *
 * Управление генерацией и скачиванием отчётов
 * по уведомлениям пользователя.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    /**
     * Запросить генерацию отчёта.
     *
     * POST /api/reports
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->reportService->createReport(
            (int) $request->input('user_id'),
            $request->input('date_from'),
            $request->input('date_to'),
        );

        if ($report->status === 'pending') {
            GenerateReportJob::dispatch($report);
        }

        return response()->json([
            'data' => [
                'id' => $report->id,
                'status' => $report->status,
                'date_from' => $report->date_from->toIso8601String(),
                'date_to' => $report->date_to->toIso8601String(),
            ],
        ], $report->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Получить статус отчёта.
     *
     * GET /api/reports/{id}
     */
    public function show(Report $report): JsonResponse
    {
        $data = [
            'id' => $report->id,
            'status' => $report->status,
            'date_from' => $report->date_from->toIso8601String(),
            'date_to' => $report->date_to->toIso8601String(),
        ];

        if ($report->status === 'failed') {
            $data['error_message'] = $report->error_message;
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Скачать файл отчёта.
     *
     * GET /api/reports/{id}/download
     */
    public function download(Report $report): StreamedResponse|JsonResponse
    {
        if ($report->status !== 'ready') {
            return response()->json([
                'message' => 'Отчёт ещё не готов или ошибка генерации.',
            ], 400);
        }

        if ($report->file_path === null || ! Storage::disk('local')->exists($report->file_path)) {
            return response()->json([
                'message' => 'Файл отчёта не найден.',
            ], 404);
        }

        return Storage::disk('local')->download($report->file_path);
    }
}
