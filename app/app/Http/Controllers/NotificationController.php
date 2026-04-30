<?php

namespace App\Http\Controllers;

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
        $paginator = $this->service->filter($request->validated());

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
}
