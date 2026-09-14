<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Event;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Event\NotificationSkippedEvent;
use Crustum\Notification\Notification;

/**
 * NotificationSkippedEvent Test Case
 */
class NotificationSkippedEventTest extends TestCase
{
    /**
     * Test constructor sets event data correctly
     *
     * @return void
     */
    public function testConstructorSetsEventData(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notification = $this->createStub(Notification::class);
        $channel = 'mail';

        $event = new NotificationSkippedEvent($notifiable, $notification, $channel);

        $this->assertEquals(NotificationSkippedEvent::NAME, $event->getName());
        $this->assertSame($notifiable, $event->getData('notifiable'));
        $this->assertSame($notification, $event->getData('notification'));
        $this->assertEquals($channel, $event->getData('channel'));
    }

    /**
     * Test getNotifiable returns correct entity
     *
     * @return void
     */
    public function testGetNotifiableReturnsEntity(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notification = $this->createStub(Notification::class);
        $channel = 'database';

        $event = new NotificationSkippedEvent($notifiable, $notification, $channel);

        $this->assertSame($notifiable, $event->getNotifiable());
        $this->assertInstanceOf(EntityInterface::class, $event->getNotifiable());
    }

    /**
     * Test getNotification returns correct notification
     *
     * @return void
     */
    public function testGetNotificationReturnsNotification(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notification = $this->createStub(Notification::class);
        $channel = 'database';

        $event = new NotificationSkippedEvent($notifiable, $notification, $channel);

        $this->assertSame($notification, $event->getNotification());
        $this->assertInstanceOf(Notification::class, $event->getNotification());
    }

    /**
     * Test getChannel returns correct channel name
     *
     * @return void
     */
    public function testGetChannelReturnsChannelName(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notification = $this->createStub(Notification::class);
        $channel = 'mail';

        $event = new NotificationSkippedEvent($notifiable, $notification, $channel);

        $this->assertEquals('mail', $event->getChannel());
    }

    /**
     * Test event name constant
     *
     * @return void
     */
    public function testEventNameConstant(): void
    {
        $this->assertEquals('Model.Notification.skipped', NotificationSkippedEvent::NAME);
    }
}
