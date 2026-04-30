<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_requires_user_id(): void
    {
        $response = $this->postJson('/api/notifications', [
            'message' => 'Test',
            'channels' => ['email'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_store_requires_message(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 1,
            'channels' => ['email'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_store_message_max500_chars(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 1,
            'message' => str_repeat('a', 501),
            'channels' => ['email'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_store_requires_channels(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 1,
            'message' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channels']);
    }

    public function test_store_requires_at_least_one_channel(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 1,
            'message' => 'Test',
            'channels' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channels']);
    }

    public function test_store_rejects_invalid_channel(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 1,
            'message' => 'Test',
            'channels' => ['sms'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channels.0']);
    }

    public function test_list_validates_invalid_status(): void
    {
        $response = $this->getJson('/api/notifications?status=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_list_validates_invalid_channel(): void
    {
        $response = $this->getJson('/api/notifications?channel=sms');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);
    }

    public function test_list_validates_date_to_after_date_from(): void
    {
        $response = $this->getJson('/api/notifications?date_from=2024-12-01&date_to=2024-01-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }
}
