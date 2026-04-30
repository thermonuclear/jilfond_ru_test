<?php

namespace App\Repositories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Репозиторий уведомлений.
 *
 * Инкапсурует все операции с базой данных,
 * изолируя Eloquent от сервисного слоя.
 */
class NotificationRepository
{
    /**
     * Создать новое уведомление.
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    /**
     * Найти уведомление по ID.
     */
    public function find(int $id): ?Notification
    {
        return Notification::find($id);
    }

    /**
     * Получить уведомления с фильтрацией и пагинацией.
     *
     * @param array{
     *     user_id?: int,
     *     status?: NotificationStatus,
     *     channel?: NotificationChannel,
     *     date_from?: string,
     *     date_to?: string,
     * } $filters
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Notification::query();

        if (isset($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['channel'])) {
            $query->byChannel($filters['channel']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->dateRange($filters['date_from'], $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Получить все уведомления пользователя.
     *
     * @return Collection<int, Notification>
     */
    public function getByUser(int $userId): Collection
    {
        return Notification::forUser($userId)->latest()->get();
    }

    /**
     * Обновить статус уведомления на «отправлено».
     */
    public function markAsSent(Notification $notification): void
    {
        $notification->update([
            'status' => NotificationStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    /**
     * Обновить статус уведомления на «ошибка».
     */
    public function markAsFailed(Notification $notification, string $error): void
    {
        $notification->update([
            'status' => NotificationStatus::Failed,
            'last_error' => $error,
        ]);
    }

    /**
     * Увеличить счётчик попыток отправки.
     */
    public function incrementAttempts(Notification $notification): void
    {
        $notification->increment('attempts');
    }
}
