<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Crustum\Notification\NotificationManager;
use Crustum\Notification\NotificationSender;
use ReflectionClass;
use Throwable;

/**
 * NotificationSender Test Case
 *
 * Tests the notification sending logic and event dispatching
 */
class NotificationSenderTest extends TestCase
{
    /**
     * Fixtures to load
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Crustum/Notification.Notifications',
    ];

    /**
     * Set up test case
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (NotificationManager::getRegistry()->has('database')) {
            NotificationManager::drop('database');
        }

        NotificationManager::setConfig('database', [
            'className' => 'Crustum/Notification.Database',
        ]);
    }

    /**
     * Tear down test case
     *
     * @return void
     */
    public function tearDown(): void
    {
        NotificationManager::drop('database');
        NotificationManager::getRegistry()->reset();

        parent::tearDown();
    }

    /**
     * Test that sendNow sends notification through channel
     *
     * @return void
     */
    public function testSendNowSendsNotification(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $sender->sendNow($entity, $notification);

        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');
        $saved = $notificationsTable->find()
            ->where(['type' => TestDatabaseNotification::class])
            ->first();

        $this->assertNotNull($saved);
        $this->assertEquals('Users', $saved->model);
        $this->assertEquals('1', $saved->foreign_key);
        $this->assertEquals(['message' => 'Test message'], $saved->data);
    }

    /**
     * Test that sendNow dispatches sent event
     *
     * @return void
     */
    public function testSendNowDispatchesSentEvent(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $eventFired = false;
        $sender->getEventManager()->on('Model.Notification.sent', function () use (&$eventFired) {
            $eventFired = true;
        });

        $sender->sendNow($entity, $notification);

        $this->assertTrue($eventFired);
    }

    /**
     * Test that formatNotifiables handles single entity
     *
     * @return void
     */
    public function testFormatNotifiablesHandlesSingleEntity(): void
    {
        $sender = new NotificationSender();
        $entity = new Entity();

        $reflection = new ReflectionClass($sender);
        $method = $reflection->getMethod('formatNotifiables');

        $result = $method->invoke($sender, $entity);

        $this->assertIsIterable($result);
        $resultArray = is_array($result) ? $result : iterator_to_array($result);
        $this->assertEquals([$entity], $resultArray);
    }

    /**
     * Test that formatNotifiables handles array of entities
     *
     * @return void
     */
    public function testFormatNotifiablesHandlesArrayOfEntities(): void
    {
        $sender = new NotificationSender();
        $entity1 = new Entity(['id' => 1]);
        $entity2 = new Entity(['id' => 2]);
        $entities = [$entity1, $entity2];

        $reflection = new ReflectionClass($sender);
        $method = $reflection->getMethod('formatNotifiables');

        $result = $method->invoke($sender, $entities);

        $this->assertIsIterable($result);
        $resultArray = is_array($result) ? $result : iterator_to_array($result);
        $this->assertCount(2, $resultArray);
        $this->assertSame($entity1, $resultArray[0]);
        $this->assertSame($entity2, $resultArray[1]);
    }

    /**
     * Test that sendNow handles multiple notifiables
     *
     * @return void
     */
    public function testSendNowHandlesMultipleNotifiables(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity1 = new Entity(['id' => 1]);
        $entity1->setSource('Users');
        $entity2 = new Entity(['id' => 2]);
        $entity2->setSource('Users');

        $sender->sendNow([$entity1, $entity2], $notification);

        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');
        $saved = $notificationsTable->find()
            ->where(['type' => TestDatabaseNotification::class])
            ->toArray();

        $this->assertCount(2, $saved);
    }

    /**
     * Test that sendNow dispatches sending event
     *
     * @return void
     */
    public function testSendNowDispatchesSendingEvent(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $eventFired = false;
        $sender->getEventManager()->on('Model.Notification.sending', function () use (&$eventFired) {
            $eventFired = true;
        });

        $sender->sendNow($entity, $notification);

        $this->assertTrue($eventFired);
    }

    /**
     * Test that sendNow dispatches failed event on exception
     *
     * @return void
     */
    public function testSendNowDispatchesFailedEventOnException(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        NotificationManager::drop('database');
        NotificationManager::setConfig('database', [
            'className' => 'NonExistentChannel',
        ]);

        $eventFired = false;
        $exceptionCaught = null;
        $sender->getEventManager()->on('Model.Notification.failed', function ($event) use (&$eventFired, &$exceptionCaught) {
            $eventFired = true;
            $exceptionCaught = $event->getData('exception');
        });

        try {
            $sender->sendNow($entity, $notification);
        } catch (Throwable $e) {
            $this->assertTrue($eventFired, 'Failed event should have been dispatched');
            $this->assertNotNull($exceptionCaught, 'Exception should have been caught in event');
        }
    }

    /**
     * Test that sendNow uses specified channels
     *
     * @return void
     */
    public function testSendNowUsesSpecifiedChannels(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $channelsCalled = [];
        $sender->getEventManager()->on('Model.Notification.sent', function ($event) use (&$channelsCalled) {
            $channelsCalled[] = $event->getData('channel');
        });

        $sender->sendNow($entity, $notification, ['database']);

        $this->assertContains('database', $channelsCalled);
    }

    /**
     * Test that sendNow skips empty channels
     *
     * @return void
     */
    public function testSendNowSkipsEmptyChannels(): void
    {
        $sender = new NotificationSender();
        $notification = new class extends TestDatabaseNotification {
            public function via($notifiable): array
            {
                return [];
            }
        };

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $eventFired = false;
        $sender->getEventManager()->on('Model.Notification.sent', function () use (&$eventFired) {
            $eventFired = true;
        });

        $sender->sendNow($entity, $notification);

        $this->assertFalse($eventFired);
    }

    /**
     * Test getRoutingInfo with entity that has routing method
     *
     * @return void
     */
    public function testGetRoutingInfoWithEntityRoutingMethod(): void
    {
        $sender = new NotificationSender();
        $entity = new class extends Entity {
            public function routeNotificationForMail(): string
            {
                return 'test@example.com';
            }
        };

        $result = $sender->getRoutingInfo($entity, 'mail');

        $this->assertEquals('test@example.com', $result);
    }

    /**
     * Test getRoutingInfo returns null when method doesn't exist
     *
     * @return void
     */
    public function testGetRoutingInfoReturnsNullWhenMethodDoesNotExist(): void
    {
        $sender = new NotificationSender();
        $entity = new Entity(['id' => 1]);

        $result = $sender->getRoutingInfo($entity, 'mail');

        $this->assertNull($result);
    }

    /**
     * Test that sendNow sets notification ID on clone
     *
     * @return void
     */
    public function testSendNowSetsNotificationId(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $notificationId = null;
        $sender->getEventManager()->on('Model.Notification.sent', function ($event) use (&$notificationId) {
            $notificationId = $event->getData('notification')->getId();
        });

        $sender->sendNow($entity, $notification);

        $this->assertNotNull($notificationId);
        $this->assertNotEmpty($notificationId);
    }

    /**
     * Test that sendNow preserves existing notification ID on clone
     *
     * @return void
     */
    public function testSendNowPreservesExistingNotificationId(): void
    {
        $sender = new NotificationSender();
        $notification = new TestDatabaseNotification();
        $notification->setId('custom-id-123');

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $notificationId = null;
        $sender->getEventManager()->on('Model.Notification.sent', function ($event) use (&$notificationId) {
            $notificationId = $event->getData('notification')->getId();
        });

        $sender->sendNow($entity, $notification);

        $this->assertEquals('custom-id-123', $notificationId);
    }
}
