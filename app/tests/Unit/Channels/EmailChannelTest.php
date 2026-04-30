<?php

namespace Tests\Unit\Channels;

use App\Channels\EmailChannel;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Tests\TestCase;

class EmailChannelTest extends TestCase
{
    public function test_send_returns_true(): void
    {
        $channel = new EmailChannel;
        $notification = new Notification([
            'id' => 1,
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Test',
            'status' => NotificationStatus::Pending,
        ]);

        $result = $channel->send($notification);

        self::assertSame(true, $result);
    }
}
