<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Extension;

use Cake\TestSuite\TestCase;
use Crustum\Notification\Extension\ChannelProviderInterface;
use Crustum\Notification\Registry\ChannelRegistry;

/**
 * ChannelProviderInterface Test
 *
 * Tests the channel provider interface implementation
 */
class ChannelProviderInterfaceTest extends TestCase
{
    /**
     * Test that mock provider implements interface correctly
     *
     * @return void
     */
    public function testProviderImplementsInterface(): void
    {
        $provider = new MockChannelProvider();

        $this->assertInstanceOf(ChannelProviderInterface::class, $provider);
        $this->assertIsArray($provider->provides());
        $this->assertIsArray($provider->getDefaultConfig());
    }

    /**
     * Test provider registration
     *
     * @return void
     */
    public function testProviderRegistersChannel(): void
    {
        $registry = new ChannelRegistry();
        $provider = new MockChannelProvider();

        $provider->register($registry);

        $this->assertTrue($registry->has('mock'));
    }

    /**
     * Test provider provides method
     *
     * @return void
     */
    public function testProviderProvidesChannelNames(): void
    {
        $provider = new MockChannelProvider();

        $channels = $provider->provides();

        $this->assertContains('mock', $channels);
    }

    /**
     * Test provider default config
     *
     * @return void
     */
    public function testProviderReturnsDefaultConfig(): void
    {
        $provider = new MockChannelProvider();

        $config = $provider->getDefaultConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('timeout', $config);
    }
}
