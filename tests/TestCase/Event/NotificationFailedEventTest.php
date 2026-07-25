<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Event;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Event\NotificationFailedEvent;
use Crustum\Notification\Notification;
use Exception;
use Throwable;

/**
 * NotificationFailedEvent Test Case
 */
class NotificationFailedEventTest extends TestCase
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
        $exception = new Exception('Failed to send');

        $event = new NotificationFailedEvent($notifiable, $notification, $channel, $exception);

        $this->assertEquals(NotificationFailedEvent::NAME, $event->getName());
        $this->assertSame($notifiable, $event->getData('notifiable'));
        $this->assertSame($notification, $event->getData('notification'));
        $this->assertEquals($channel, $event->getData('channel'));
        $this->assertSame($exception, $event->getData('exception'));
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
        $exception = new Exception('Failed');

        $event = new NotificationFailedEvent($notifiable, $notification, $channel, $exception);

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
        $exception = new Exception('Failed');

        $event = new NotificationFailedEvent($notifiable, $notification, $channel, $exception);

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
        $exception = new Exception('Failed');

        $event = new NotificationFailedEvent($notifiable, $notification, $channel, $exception);

        $this->assertEquals('mail', $event->getChannel());
    }

    /**
     * Test getException returns correct exception
     *
     * @return void
     */
    public function testGetExceptionReturnsException(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notification = $this->createStub(Notification::class);
        $channel = 'mail';
        $exception = new Exception('Failed to send notification');

        $event = new NotificationFailedEvent($notifiable, $notification, $channel, $exception);

        $this->assertSame($exception, $event->getException());
        $this->assertInstanceOf(Throwable::class, $event->getException());
        $this->assertEquals('Failed to send notification', $event->getException()->getMessage());
    }

    /**
     * Test event name constant
     *
     * @return void
     */
    public function testEventNameConstant(): void
    {
        $this->assertEquals('Model.Notification.failed', NotificationFailedEvent::NAME);
    }
}
