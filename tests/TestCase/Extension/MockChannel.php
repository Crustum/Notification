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
     * Channel configuration
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * @param array<string, mixed> $config Channel configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function send(object $notifiable, Notification $notification): mixed
    {
        return null;
    }
}
