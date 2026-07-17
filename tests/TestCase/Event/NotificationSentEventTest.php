<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Event;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Event\NotificationSentEvent;
use Crustum\Notification\Notification;

/**
 * NotificationSentEvent Test Case
 */
class NotificationSentEventTest extends TestCase
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
        $channel = 'database';
        $response = ['id' => 123];

        $event = new NotificationSentEvent($notifiable, $notification, $channel, $response);

        $this->assertEquals(NotificationSentEvent::NAME, $event->getName());
        $this->assertSame($notifiable, $event->getData('notifiable'));
        $this->assertSame($notification, $event->getData('notification'));
        $this->assertEquals($channel, $event->getData('channel'));
        $this->assertSame($response, $event->getData('response'));
    }

    /**
     * Test constructor with null response
     *
     * @return void
     */
    public function testConstructorWithNullResponse(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notification = $this->createStub(Notification::class);
        $channel = 'mail';

        $event = new NotificationSentEvent($notifiable, $notification, $channel);

        $this->assertNull($event->getData('response'));
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

        $event = new NotificationSentEvent($notifiable, $notification, $channel);

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

        $event = new NotificationSentEvent($notifiable, $notification, $channel);

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

        $event = new NotificationSentEvent($notifiable, $notification, $channel);

        $this->assertEquals('mail', $event->getChannel());
    }

    /**
     * Test getResponse returns correct response
     *
     * @return void
     */
    public function testGetResponseReturnsResponse(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notification = $this->createStub(Notification::class);
        $channel = 'database';
        $response = ['id' => 123, 'status' => 'sent'];

        $event = new NotificationSentEvent($notifiable, $notification, $channel, $response);

        $this->assertSame($response, $event->getResponse());
    }

    /**
     * Test getResponse returns null when not provided
     *
     * @return void
     */
    public function testGetResponseReturnsNullWhenNotProvided(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notification = $this->createStub(Notification::class);
        $channel = 'mail';

        $event = new NotificationSentEvent($notifiable, $notification, $channel);

        $this->assertNull($event->getResponse());
    }

    /**
     * Test event name constant
     *
     * @return void
     */
    public function testEventNameConstant(): void
    {
        $this->assertEquals('Model.Notification.sent', NotificationSentEvent::NAME);
    }
}
