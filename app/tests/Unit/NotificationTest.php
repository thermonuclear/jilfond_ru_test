<?php

namespace Tests\Unit;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_channel_and_status_to_enums(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Test',
            'status' => NotificationStatus::Pending,
        ]);

        self::assertInstanceOf(NotificationChannel::class, $notification->channel);
        self::assertInstanceOf(NotificationStatus::class, $notification->status);
        self::assertEquals(NotificationChannel::Email, $notification->channel);
        self::assertEquals(NotificationStatus::Pending, $notification->status);
    }

    public function test_scope_by_status(): void
    {
        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Test 1',
            'status' => NotificationStatus::Sent,
        ]);

        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Test 2',
            'status' => NotificationStatus::Failed,
        ]);

        $pending = Notification::byStatus('sent')->get();
        self::assertCount(1, $pending);
        self::assertEquals(NotificationStatus::Sent, $pending->first()->status);
    }

    public function test_scope_by_channel(): void
    {
        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Email test',
            'status' => NotificationStatus::Pending,
        ]);

        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Telegram test',
            'status' => NotificationStatus::Pending,
        ]);

        $email = Notification::byChannel('email')->get();
        self::assertCount(1, $email);
        self::assertEquals(NotificationChannel::Email, $email->first()->channel);
    }

    public function test_scope_for_user(): void
    {
        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'User 1',
            'status' => NotificationStatus::Pending,
        ]);

        Notification::create([
            'user_id' => 2,
            'channel' => NotificationChannel::Email,
            'message' => 'User 2',
            'status' => NotificationStatus::Pending,
        ]);

        $user1 = Notification::forUser(1)->get();
        self::assertCount(1, $user1);
        self::assertEquals(1, $user1->first()->user_id);
    }

    public function test_scope_date_range(): void
    {
        $old = new Notification([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Old',
            'status' => NotificationStatus::Pending,
        ]);
        $old->created_at = '2024-01-01';
        $old->save();

        $new = new Notification([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'New',
            'status' => NotificationStatus::Pending,
        ]);
        $new->created_at = '2024-06-01';
        $new->save();

        $range = Notification::dateRange('2024-05-01', '2024-07-01')->get();
        self::assertCount(1, $range);
        self::assertEquals('New', $range->first()->message);
    }
}
