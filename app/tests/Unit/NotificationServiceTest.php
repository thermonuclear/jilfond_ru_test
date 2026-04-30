<?php

namespace Tests\Unit;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_creates_notification_for_each_channel(): void
    {
        $service = app(NotificationService::class);

        $result = $service->create([
            'user_id' => 1,
            'message' => 'Test message',
            'channels' => ['email', 'telegram'],
        ]);

        self::assertCount(2, $result);
        self::assertEquals(NotificationChannel::Email, $result[0]->channel);
        self::assertEquals(NotificationChannel::Telegram, $result[1]->channel);
        self::assertEquals(NotificationStatus::Pending, $result[0]->status);
        self::assertEquals(NotificationStatus::Pending, $result[1]->status);
    }

    public function test_send_calls_email_channel(): void
    {
        Log::shouldReceive('info')->once()->andReturnNull();

        $service = app(NotificationService::class);

        $notification = Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Test',
            'status' => NotificationStatus::Pending,
        ]);

        $service->send($notification);
    }

    public function test_send_calls_telegram_channel(): void
    {
        Log::shouldReceive('info')->once()->andReturnNull();

        $service = app(NotificationService::class);

        $notification = Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Test',
            'status' => NotificationStatus::Pending,
        ]);

        $service->send($notification);
    }

    public function test_mark_as_sent_updates_status(): void
    {
        $service = app(NotificationService::class);

        $notification = Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Test',
            'status' => NotificationStatus::Pending,
        ]);

        $service->markAsSent($notification);
        $notification->refresh();

        self::assertEquals(NotificationStatus::Sent, $notification->status);
        self::assertNotNull($notification->sent_at);
    }

    public function test_mark_as_failed_updates_status_with_error(): void
    {
        $service = app(NotificationService::class);

        $notification = Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Test',
            'status' => NotificationStatus::Pending,
            'attempts' => 0,
        ]);

        $service->markAsFailed($notification, 'Connection timeout');
        $notification->refresh();

        self::assertEquals(NotificationStatus::Failed, $notification->status);
        self::assertEquals('Connection timeout', $notification->last_error);
    }
}
