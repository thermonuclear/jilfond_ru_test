<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_notification(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 1,
            'message' => 'Hello world',
            'channels' => ['email'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.0.user_id', 1)
            ->assertJsonPath('data.0.message', 'Hello world')
            ->assertJsonPath('data.0.channel', 'email')
            ->assertJsonPath('data.0.status', 'pending');

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_store_creates_multiple_channels(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 1,
            'message' => 'Multi-channel',
            'channels' => ['email', 'telegram'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonCount(2, 'data');
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_show_returns_notification(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Test',
            'status' => NotificationStatus::Pending,
        ]);

        $response = $this->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_index_returns_all_notifications(): void
    {
        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'First',
            'status' => NotificationStatus::Pending,
        ]);

        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Second',
            'status' => NotificationStatus::Sent,
        ]);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'user_id', 'channel', 'message', 'status']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_index_filters_by_status(): void
    {
        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Pending',
            'status' => NotificationStatus::Pending,
        ]);

        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Sent',
            'status' => NotificationStatus::Sent,
        ]);

        $response = $this->getJson('/api/notifications?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_index_filters_by_channel(): void
    {
        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Email',
            'status' => NotificationStatus::Pending,
        ]);

        Notification::create([
            'user_id' => 1,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Telegram',
            'status' => NotificationStatus::Pending,
        ]);

        $response = $this->getJson('/api/notifications?channel=telegram');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.channel', 'telegram');
    }
}
