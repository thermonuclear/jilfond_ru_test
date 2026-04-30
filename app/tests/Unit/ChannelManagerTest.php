<?php

namespace Tests\Unit;

use App\Channels\EmailChannel;
use App\Channels\NotificationChannelInterface;
use App\Channels\TelegramChannel;
use App\Models\Notification;
use App\Services\ChannelManager;
use InvalidArgumentException;
use Tests\TestCase;

class ChannelManagerTest extends TestCase
{
    public function test_returns_email_channel(): void
    {
        $manager = app(ChannelManager::class);
        $channel = $manager->driver('email');

        self::assertInstanceOf(EmailChannel::class, $channel);
    }

    public function test_returns_telegram_channel(): void
    {
        $manager = app(ChannelManager::class);
        $channel = $manager->driver('telegram');

        self::assertInstanceOf(TelegramChannel::class, $channel);
    }

    public function test_throws_exception_for_unknown_channel(): void
    {
        $manager = app(ChannelManager::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Notification channel [sms] is not supported.');

        $manager->driver('sms');
    }

    public function test_extend_adds_new_channel(): void
    {
        $manager = app(ChannelManager::class);

        $stubChannel = new class implements NotificationChannelInterface
        {
            public function send(Notification $notification): bool
            {
                return true;
            }
        };

        $manager->extend('sms', fn () => $stubChannel);

        $channel = $manager->driver('sms');
        self::assertSame($stubChannel, $channel);
    }
}
