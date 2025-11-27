<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\TestSuite;

use Cake\TestSuite\TestCase;
use Crustum\Notification\NotificationManager;
use Crustum\Notification\TestSuite\TestNotificationSender;
use TestApp\Notification\PostPublished;

/**
 * TestNotificationSender Test
 *
 * Tests the TestNotificationSender functionality
 *
 * @uses \Crustum\Notification\TestSuite\TestNotificationSender
 */
class TestNotificationSenderTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Crustum/Notification.Users',
        'plugin.Crustum/Notification.Posts',
        'plugin.Crustum/Notification.Notifications',
    ];

    /**
     * Test setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        TestNotificationSender::replaceAllSenders();
        TestNotificationSender::clearNotifications();
    }

    /**
     * Test teardown
     *
     * @return void
     */
    public function tearDown(): void
    {
        TestNotificationSender::clearNotifications();
        parent::tearDown();
    }

    /**
     * Test replaceAllSenders configures the sender
     *
     * @return void
     */
    public function testReplaceAllSenders(): void
    {
        TestNotificationSender::replaceAllSenders();

        $sender = NotificationManager::getSender();

        $this->assertInstanceOf(TestNotificationSender::class, $sender);
    }

    /**
     * Test send captures notification
     *
     * @return void
     */
    public function testSendCapturesNotification(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test'));

        $notifications = TestNotificationSender::getNotifications();

        $this->assertCount(1, $notifications);
        $this->assertEquals(PostPublished::class, $notifications[0]['notification_class']);
    }

    /**
     * Test sendNow captures notification
     *
     * @return void
     */
    public function testSendNowCapturesNotification(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        NotificationManager::sendNow($user, new PostPublished(1, 'Test'));

        $notifications = TestNotificationSender::getNotifications();

        $this->assertCount(1, $notifications);
        $this->assertFalse($notifications[0]['queued']);
    }

    /**
     * Test clearNotifications
     *
     * @return void
     */
    public function testClearNotifications(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test'));

        $this->assertCount(1, TestNotificationSender::getNotifications());

        TestNotificationSender::clearNotifications();

        $this->assertCount(0, TestNotificationSender::getNotifications());
    }

    /**
     * Test getNotificationsFor
     *
     * @return void
     */
    public function testGetNotificationsFor(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user1 = $usersTable->get(1);
        $user2 = $usersTable->get(2);

        $usersTable->notify($user1, new PostPublished(1, 'Test'));
        $usersTable->notify($user2, new PostPublished(2, 'Test'));

        $user1Notifications = TestNotificationSender::getNotificationsFor($user1, PostPublished::class);
        $user2Notifications = TestNotificationSender::getNotificationsFor($user2, PostPublished::class);

        $this->assertCount(1, $user1Notifications);
        $this->assertCount(1, $user2Notifications);
    }

    /**
     * Test getNotificationsByChannel
     *
     * @return void
     */
    public function testGetNotificationsByChannel(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test'));

        $databaseNotifications = TestNotificationSender::getNotificationsByChannel('database');
        $mailNotifications = TestNotificationSender::getNotificationsByChannel('mail');

        $this->assertNotEmpty($databaseNotifications);
        $this->assertNotEmpty($mailNotifications);
    }

    /**
     * Test getNotificationsByClass
     *
     * @return void
     */
    public function testGetNotificationsByClass(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test'));
        $usersTable->notify($user, new PostPublished(2, 'Test'));

        $notifications = TestNotificationSender::getNotificationsByClass(PostPublished::class);

        $this->assertCount(2, $notifications);
    }

    /**
     * Test getOnDemandNotifications
     *
     * @return void
     */
    public function testGetOnDemandNotifications(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);
        $anonymous = NotificationManager::route('mail', 'admin@example.com');

        $usersTable->notify($user, new PostPublished(1, 'Test'));
        $anonymous->notify(new PostPublished(2, 'Admin Post'));

        $onDemandNotifications = TestNotificationSender::getOnDemandNotifications();

        $this->assertCount(1, $onDemandNotifications);
    }
}
