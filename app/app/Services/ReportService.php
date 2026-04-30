<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Сервис генерации отчётов по уведомлениям.
 *
 * Формирует CSV-отчёт со статистикой по каналам
 * и ошибкам за указанный период.
 */
class ReportService
{
    /**
     * Диск хранилища для отчётов.
     */
    private const DISK = 'local';

    /**
     * Директория для хранения файлов отчётов.
     */
    private const DIRECTORY = 'reports';

    /**
     * Создать запись отчёта со статусом pending.
     *
     * Если уже существует pending или ready отчёт
     * за тот же период — возвращает его.
     */
    public function createReport(int $userId, string $dateFrom, string $dateTo): Report
    {
        $existing = Report::query()
            ->where('user_id', $userId)
            ->where('date_from', $dateFrom)
            ->where('date_to', $dateTo)
            ->whereIn('status', ['pending', 'ready'])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Report::create([
            'user_id' => $userId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => 'pending',
        ]);
    }

    /**
     * Сгенерировать CSV-отчёт.
     *
     * @throws \Throwable при ошибке генерации
     */
    public function generate(Report $report): void
    {
        $data = $this->collectData($report);
        $csv = $this->buildCsv($data);
        $path = $this->storeFile($report, $csv);

        $report->update([
            'status' => 'ready',
            'file_path' => $path,
        ]);
    }

    /**
     * Собрать статистику из базы данных.
     *
     * @return array<int, array{
     *     channel: string,
     *     total: int,
     *     errors: int,
     * }>
     */
    private function collectData(Report $report): array
    {
        /** @var Collection<int, object{channel: string, total: string|int, errors: string|int}> $stats */
        $stats = Notification::query()
            ->where('user_id', $report->user_id)
            ->whereBetween('created_at', [
                $report->date_from->startOfDay(),
                $report->date_to->endOfDay(),
            ])
            ->selectRaw('channel, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as errors', ['failed'])
            ->groupBy('channel')
            ->get();

        return $stats->map(fn ($row) => [
            'channel' => $row->channel,
            'total' => (int) $row->total,
            'errors' => (int) $row->errors,
        ])->all();
    }

    /**
     * Построить CSV-строку из данных.
     *
     * @param  array<int, array{channel: string, total: int, errors: int}>  $data
     */
    private function buildCsv(array $data): string
    {
        $handle = fopen('php://temp', 'r+');

        // Заголовки
        fputcsv($handle, ['Канал', 'Количество уведомлений', 'Количество ошибок']);

        // Данные
        foreach ($data as $row) {
            fputcsv($handle, [
                $row['channel'],
                $row['total'],
                $row['errors'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Сохранить CSV-файл в хранилище.
     */
    private function storeFile(Report $report, string $csv): string
    {
        $filename = sprintf(
            '%s/report_%d_%s_%s.csv',
            self::DIRECTORY,
            $report->user_id,
            $report->date_from->format('Y-m-d'),
            $report->date_to->format('Y-m-d')
        );

        Storage::disk(self::DISK)->put($filename, $csv);

        return $filename;
    }

    /**
     * Удалить файл отчёта.
     */
    public function deleteFile(?string $filePath): void
    {
        if ($filePath !== null && Storage::disk(self::DISK)->exists($filePath)) {
            Storage::disk(self::DISK)->delete($filePath);
        }
    }
}
