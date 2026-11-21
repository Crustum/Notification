<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Crustum\Notification\Channel\ChannelInterface;
use Crustum\Notification\Notification;
use RuntimeException;

/**
 * Channel that always throws when sending
 */
class TestThrowingChannel implements ChannelInterface
{
    /**
     * @inheritDoc
     */
    public function send(object $notifiable, Notification $notification): mixed
    {
        throw new RuntimeException('SMS gateway down');
    }
}
