<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Repositories\NotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Сервис уведомлений.
 *
 * Содержит бизнес-логику: создание, отправка,
 * управление статусами. Оркестрирует репозиторий
 * и менеджер каналов.
 */
class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $repository,
        private readonly ChannelManager $channelManager,
    ) {}

    /**
     * Создать уведомления для каждого указанного канала.
     *
     * @param array{
     *     user_id: int,
     *     message: string,
     *     channels: array<string>,
     * } $data
     * @return Collection<int, Notification>
     */
    public function create(array $data): Collection
    {
        $notifications = collect();

        foreach ($data['channels'] as $channel) {
            $notification = $this->repository->create([
                'user_id' => $data['user_id'],
                'channel' => $channel,
                'message' => $data['message'],
                'status' => NotificationStatus::Pending,
            ]);

            SendNotificationJob::dispatch($notification);

            $notifications->push($notification);
        }

        return $notifications;
    }

    /**
     * Отправить уведомление через соответствующий канал.
     */
    public function send(Notification $notification): void
    {
        $channel = $this->channelManager->driver($notification->channel->value);
        $channel->send($notification);
    }

    /**
     * Отметить уведомление как отправленное.
     */
    public function markAsSent(Notification $notification): void
    {
        $this->repository->markAsSent($notification);
    }

    /**
     * Отметить уведомление как ошибочное.
     */
    public function markAsFailed(Notification $notification, string $error): void
    {
        $this->repository->incrementAttempts($notification);
        $this->repository->markAsFailed($notification, $error);
    }

    /**
     * Получить уведомление по ID.
     */
    public function find(int $id): ?Notification
    {
        return $this->repository->find($id);
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
        return $this->repository->filter($filters, $perPage);
    }
}
