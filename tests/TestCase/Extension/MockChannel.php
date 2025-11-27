<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Extension;

use Crustum\Notification\Channel\ChannelInterface;
use Crustum\Notification\Notification;

/**
 * Mock Channel for testing
 */
class MockChannel implements ChannelInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(array $config = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function send(object $notifiable, Notification $notification): mixed
    {
        return null;
    }
}
