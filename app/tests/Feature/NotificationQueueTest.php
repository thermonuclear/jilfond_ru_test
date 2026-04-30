<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_dispatched_on_create(): void
    {
        Queue::fake();

        $service = app(NotificationService::class);
        $service->create([
            'user_id' => 1,
            'message' => 'Test',
            'channels' => ['email'],
        ]);

        Queue::assertPushed(SendNotificationJob::class);
    }

    public function test_job_dispatched_for_each_channel(): void
    {
        Queue::fake();

        $service = app(NotificationService::class);
        $service->create([
            'user_id' => 1,
            'message' => 'Test',
            'channels' => ['email', 'telegram'],
        ]);

        Queue::assertPushed(SendNotificationJob::class, 2);
    }

    public function test_job_marks_as_sent_on_success(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'channel' => 'email',
            'message' => 'Test',
            'status' => 'pending',
        ]);

        $job = new SendNotificationJob($notification);
        $job->handle(app(NotificationService::class));

        $notification->refresh();

        self::assertEquals('sent', $notification->status->value);
        self::assertNotNull($notification->sent_at);
    }

    public function test_job_marks_as_failed_on_error(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'channel' => 'email',
            'message' => 'Test',
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $job = new SendNotificationJob($notification);
        $job->failed(new \RuntimeException('Connection timeout'));

        $notification->refresh();

        self::assertEquals('failed', $notification->status->value);
        self::assertEquals('Connection timeout', $notification->last_error);
    }
}
