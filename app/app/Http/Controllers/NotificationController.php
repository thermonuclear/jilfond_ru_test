<?php

namespace App\Http\Controllers;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Http\Requests\ListNotificationsRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер уведомлений.
 *
 * Тонкий контроллер — вся бизнес-логика
 * делегирована NotificationService.
 */
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    /**
     * Создать уведомление.
     *
     * POST /api/notifications
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $notifications = $this->service->create($request->validated());

        return response()->json([
            'data' => NotificationResource::collection($notifications),
        ], 201);
    }

    /**
     * Получить уведомление по ID.
     *
     * GET /api/notifications/{id}
     */
    public function show(Notification $notification): NotificationResource
    {
        return new NotificationResource($notification);
    }

    /**
     * Получить список уведомлений с фильтрацией.
     *
     * GET /api/notifications
     */
    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $filters = $this->buildFilters($request);
        $paginator = $this->service->filter($filters);

        return response()->json([
            'data' => NotificationResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Собрать фильтры из запроса.
     *
     * @return array{
     *     user_id?: int,
     *     status?: NotificationStatus,
     *     channel?: NotificationChannel,
     *     date_from?: string,
     *     date_to?: string,
     * }
     */
    private function buildFilters(ListNotificationsRequest $request): array
    {
        $filters = [];

        if ($request->has('user_id')) {
            $filters['user_id'] = (int) $request->input('user_id');
        }

        if ($request->has('status')) {
            $filters['status'] = NotificationStatus::from($request->input('status'));
        }

        if ($request->has('channel')) {
            $filters['channel'] = NotificationChannel::from($request->input('channel'));
        }

        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }

        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }

        return $filters;
    }
}
