<?php

namespace Tests\Unit\Channels;

use App\Channels\TelegramChannel;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Tests\TestCase;

class TelegramChannelTest extends TestCase
{
    public function test_send_returns_true(): void
    {
        $channel = new TelegramChannel;
        $notification = new Notification([
            'id' => 1,
            'user_id' => 1,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Test',
            'status' => NotificationStatus::Pending,
        ]);

        $result = $channel->send($notification);

        self::assertSame(true, $result);
    }
}
