<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Registry;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Registry\ChannelRegistry;
use ReflectionClass;

/**
 * ChannelRegistry Test
 *
 * Tests the channel registry discovery event functionality
 */
class ChannelRegistryTest extends TestCase
{
    /**
     * Reset discovery flag before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $reflection = new ReflectionClass(ChannelRegistry::class);
        $property = $reflection->getProperty('_discoveryDispatched');
        $property->setValue(null, false);
    }

    /**
     * Test discovery event is dispatched
     *
     * @return void
     */
    public function testDiscoveryEventIsDispatched(): void
    {
        $eventDispatched = false;
        $capturedRegistry = null;

        EventManager::instance()->on(
            'Notification.Registry.discover',
            function (Event $event) use (&$eventDispatched, &$capturedRegistry): void {
                $eventDispatched = true;
                $capturedRegistry = $event->getSubject();
            },
        );

        $registry = new ChannelRegistry();
        $registry->dispatchDiscoveryEvent();

        $this->assertTrue($eventDispatched, 'Discovery event should be dispatched');
        $this->assertInstanceOf(ChannelRegistry::class, $capturedRegistry);
        $this->assertSame($registry, $capturedRegistry);

        EventManager::instance()->off('Notification.Registry.discover');
    }

    /**
     * Test channels can be registered via discovery event
     *
     * @return void
     */
    public function testChannelsCanBeRegisteredViaDiscoveryEvent(): void
    {
        EventManager::instance()->on(
            'Notification.Registry.discover',
            function (Event $event): void {
                $registry = $event->getSubject();
                $registry->load('test', [
                    'className' => 'Crustum\Notification\Channel\DatabaseChannel',
                ]);
            },
        );

        $registry = new ChannelRegistry();
        $registry->dispatchDiscoveryEvent();

        $this->assertTrue($registry->has('test'));

        EventManager::instance()->off('Notification.Registry.discover');
    }
}
