<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\ORM\Entity;
use Cake\Queue\QueueManager;
use Cake\Queue\TestSuite\QueueTrait as TestQueueTrait;
use Cake\Queue\TestSuite\TestQueueClient;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Job\SendQueuedNotificationJob;
use Crustum\Notification\Model\Entity\Notification as NotificationEntity;
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
    use TestQueueTrait;

    /**
     * Fixtures to load
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Crustum/Notification.Notifications',
        'plugin.Crustum/Notification.Users',
    ];

    /**
     * Set up test case
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (NotificationManager::getRegistry()->has('database')) {
            NotificationManager::drop('database');
        }

        NotificationManager::setConfig('database', [
            'className' => 'Crustum/Notification.Database',
        ]);

        if (QueueManager::getConfig('default') === null) {
            QueueManager::setConfig('default', [
                'url' => 'null:',
            ]);
        }

        if (QueueManager::getConfig('redis') === null) {
            QueueManager::setConfig('redis', [
                'url' => 'null:',
            ]);
        }

        if (QueueManager::getConfig('sync') === null) {
            QueueManager::setConfig('sync', [
                'url' => 'null:',
            ]);
        }

        TestQueueClient::replaceAllClients();
        TestQueueClient::clearQueuedJobs();
        TestNotificationWithAfterSending::reset();
    }

    /**
     * Tear down test case
     *
     * @return void
     */
    protected function tearDown(): void
    {
        NotificationManager::drop('database');
        NotificationManager::getRegistry()->reset();
        NotificationManager::resetSender();
        TestQueueClient::clearQueuedJobs();

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

        $notificationsTable = $this->getTableLocator()->get('Crustum/Notification.Notifications');
        $saved = $notificationsTable->find()
            ->where(['type' => TestDatabaseNotification::class])
            ->first();

        $this->assertInstanceOf(NotificationEntity::class, $saved);
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
        $sender->getEventManager()->on('Model.Notification.sent', function () use (&$eventFired): void {
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

        $notificationsTable = $this->getTableLocator()->get('Crustum/Notification.Notifications');
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
        $sender->getEventManager()->on('Model.Notification.sending', function () use (&$eventFired): void {
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
        $sender->getEventManager()->on('Model.Notification.failed', function ($event) use (&$eventFired, &$exceptionCaught): void {
            $eventFired = true;
            $exceptionCaught = $event->getData('exception');
        });

        try {
            $sender->sendNow($entity, $notification);
        } catch (Throwable) {
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
        $sender->getEventManager()->on('Model.Notification.sent', function ($event) use (&$channelsCalled): void {
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
        $sender->getEventManager()->on('Model.Notification.sent', function () use (&$eventFired): void {
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
        $sender->getEventManager()->on('Model.Notification.sent', function ($event) use (&$notificationId): void {
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
        $sender->getEventManager()->on('Model.Notification.sent', function ($event) use (&$notificationId): void {
            $notificationId = $event->getData('notification')->getId();
        });

        $sender->sendNow($entity, $notification);

        $this->assertEquals('custom-id-123', $notificationId);
    }

    /**
     * Test that sendNow preserves state mutated inside via()
     *
     * @return void
     */
    public function testSendNowPreservesNotificationStateMutatedInVia(): void
    {
        $sender = new NotificationSender();
        $notification = new TestNotificationWithViaMutation();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $channelData = null;
        $sender->getEventManager()->on('Model.Notification.sent', function ($event) use (&$channelData): void {
            $channelData = $event->getData('notification')->channelData;
        });

        $sender->sendNow($entity, $notification);

        $this->assertSame('mutated-in-via', $channelData);
    }

    /**
     * Test that afterSending is invoked after a successful channel send
     *
     * @return void
     */
    public function testSendNowCallsAfterSending(): void
    {
        $sender = new NotificationSender();
        $notification = new TestNotificationWithAfterSending();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $sender->sendNow($entity, $notification);

        $this->assertSame($entity, TestNotificationWithAfterSending::$afterSendingNotifiable);
        $this->assertSame('database', TestNotificationWithAfterSending::$afterSendingChannel);
        $this->assertNotNull(TestNotificationWithAfterSending::$afterSendingResponse);
    }

    /**
     * Test viaConnections falls back to default connection for unmapped channels
     *
     * @return void
     */
    public function testQueueNotificationViaConnectionsFallback(): void
    {
        $sender = new NotificationSender();
        $notification = new TestQueuedNotificationWithViaConnections();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $sender->send($entity, $notification);

        $this->assertJobCount(2);
        $this->assertCount(1, $this->getQueuedJobsByConfig('sync'));
        $this->assertCount(1, $this->getQueuedJobsByConfig('redis'));
    }

    /**
     * Test viaQueues falls back to default queue for unmapped channels
     *
     * @return void
     */
    public function testQueueNotificationViaQueuesFallback(): void
    {
        $sender = new NotificationSender();
        $notification = new TestQueuedNotificationWithViaQueues();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $sender->send($entity, $notification);

        $this->assertJobCount(2);
        $this->assertJobQueuedToQueue('admin_notifications', SendQueuedNotificationJob::class);
        $this->assertJobQueuedToQueue('dummy', SendQueuedNotificationJob::class);
    }

    /**
     * Test send formats a single notifiable before queueing
     *
     * @return void
     */
    public function testSendFormatsSingleNotifiableForQueue(): void
    {
        $sender = new NotificationSender();
        $notification = new TestQueuedDatabaseNotification();

        $entity = new Entity(['id' => 1]);
        $entity->setSource('Users');

        $sender->send($entity, $notification);

        $this->assertJobQueued(SendQueuedNotificationJob::class);
        $this->assertJobCount(1);
    }
}
