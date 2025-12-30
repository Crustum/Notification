<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Job;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\Message;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Channel\DatabaseChannel;
use Crustum\Notification\Job\SendQueuedNotificationJob;
use Crustum\Notification\NotificationManager;
use DateTime;
use Interop\Queue\Processor;

/**
 * SendQueuedNotificationJob Test Case
 *
 * Tests the queued notification job execution
 */
class SendQueuedNotificationJobTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Crustum\Notification\Job\SendQueuedNotificationJob
     */
    protected SendQueuedNotificationJob $job;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Crustum/Notification.Users',
        'plugin.Crustum/Notification.Notifications',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->job = new SendQueuedNotificationJob();

        NotificationManager::resetSender();
        NotificationManager::setConfig('database', [
            'className' => DatabaseChannel::class,
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        NotificationManager::drop('database');
        TableRegistry::getTableLocator()->clear();

        parent::tearDown();
    }

    /**
     * Test execute method with valid data
     *
     * @return void
     */
    public function testExecuteWithValidData(): void
    {
        $notification = new TestJobNotification('Test Title');
        $serialized = serialize($notification);

        $message = $this->createMessage([
            'notifiableModel' => 'Users',
            'notifiableForeignKey' => '1',
            'notification' => $serialized,
            'channels' => ['database'],
        ]);

        $result = $this->job->execute($message);

        $this->assertEquals(Processor::ACK, $result);

        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');
        $count = $notificationsTable->find()
            ->where([
                'model' => 'Users',
                'foreign_key' => '1',
            ])
            ->count();

        $this->assertGreaterThan(0, $count);
    }

    /**
     * Test execute method with missing notifiable model
     *
     * @return void
     */
    public function testExecuteWithMissingNotifiableModel(): void
    {
        $notification = new TestJobNotification('Test Title');
        $serialized = serialize($notification);

        $message = $this->createMessage([
            'notifiableModel' => '',
            'notifiableForeignKey' => '1',
            'notification' => $serialized,
            'channels' => ['database'],
        ]);

        $result = $this->job->execute($message);

        $this->assertEquals(Processor::REJECT, $result);
    }

    /**
     * Test execute method with missing foreign key
     *
     * @return void
     */
    public function testExecuteWithMissingForeignKey(): void
    {
        $notification = new TestJobNotification('Test Title');
        $serialized = serialize($notification);

        $message = $this->createMessage([
            'notifiableModel' => 'Users',
            'notifiableForeignKey' => '',
            'notification' => $serialized,
            'channels' => ['database'],
        ]);

        $result = $this->job->execute($message);

        $this->assertEquals(Processor::REJECT, $result);
    }

    /**
     * Test execute method with missing notification
     *
     * @return void
     */
    public function testExecuteWithMissingNotification(): void
    {
        $message = $this->createMessage([
            'notifiableModel' => 'Users',
            'notifiableForeignKey' => '1',
            'notification' => '',
            'channels' => ['database'],
        ]);

        $result = $this->job->execute($message);

        $this->assertEquals(Processor::REJECT, $result);
    }

    /**
     * Test execute method with invalid notification data
     *
     * @return void
     */
    public function testExecuteWithInvalidNotificationData(): void
    {
        $message = $this->createMessage([
            'notifiableModel' => 'Users',
            'notifiableForeignKey' => '1',
            'notification' => ['invalid' => 'array'],
            'channels' => ['database'],
        ]);

        $result = $this->job->execute($message);

        $this->assertEquals(Processor::REJECT, $result);
    }

    /**
     * Test execute method with non-existent notifiable
     *
     * @return void
     */
    public function testExecuteWithNonExistentNotifiable(): void
    {
        $notification = new TestJobNotification('Test Title');
        $serialized = serialize($notification);

        $message = $this->createMessage([
            'notifiableModel' => 'Users',
            'notifiableForeignKey' => 'non-existent-uuid',
            'notification' => $serialized,
            'channels' => ['database'],
        ]);

        $result = $this->job->execute($message);

        $this->assertEquals(Processor::REQUEUE, $result);
    }

    /**
     * Test serialization preserves notification properties
     *
     * @return void
     */
    public function testSerializationPreservesProperties(): void
    {
        $original = new TestJobNotification('Test Title');
        $serialized = serialize($original);

        $restored = unserialize($serialized);

        $this->assertInstanceOf(TestJobNotification::class, $restored);
        $this->assertEquals('Test Title', $restored->getTitle());
    }

    /**
     * Test complex notification serialization
     *
     * @return void
     */
    public function testComplexNotificationSerialization(): void
    {
        $original = new TestComplexJobNotification(
            'Title',
            ['tag1', 'tag2'],
            new DateTime('2025-10-14 12:00:00'),
        );

        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $this->assertInstanceOf(TestComplexJobNotification::class, $restored);
        $this->assertEquals('Title', $restored->getTitle());
        $this->assertEquals(['tag1', 'tag2'], $restored->getTags());
        $this->assertInstanceOf(DateTime::class, $restored->getCreatedAt());
        $this->assertEquals('2025-10-14 12:00:00', $restored->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * Create a mock Message object
     *
     * @param array<string, mixed> $arguments Message arguments
     * @return \Cake\Queue\Job\Message
     */
    protected function createMessage(array $arguments): Message
    {
        $message = $this->createStub(Message::class);

        $message->method('getArgument')
            ->willReturnCallback(function ($key) use ($arguments) {
                return $arguments[$key] ?? null;
            });

        return $message;
    }

    /**
     * Load notifiable entity from database
     *
     * @param string $model Model name
     * @param string $foreignKey Primary key value
     * @return \Cake\Datasource\EntityInterface
     */
    protected function loadNotifiable(string $model, string $foreignKey): EntityInterface
    {
        $table = TableRegistry::getTableLocator()->get($model);

        return $table->get($foreignKey);
    }
}
