<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Extension;

use Crustum\Notification\Extension\ChannelProviderInterface;
use Crustum\Notification\Registry\ChannelRegistry;

/**
 * Mock Channel Provider for testing
 */
class MockChannelProvider implements ChannelProviderInterface
{
    /**
     * @inheritDoc
     */
    public function provides(): array
    {
        return ['mock'];
    }

    /**
     * @inheritDoc
     */
    public function register(ChannelRegistry $registry): void
    {
        $registry->load('mock', [
            'className' => MockChannel::class,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }
}
