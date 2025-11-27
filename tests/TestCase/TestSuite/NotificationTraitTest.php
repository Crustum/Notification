<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\TestSuite;

use Cake\TestSuite\TestCase;
use Crustum\Notification\NotificationManager;
use Crustum\Notification\TestSuite\NotificationTrait;
use TestApp\Model\Table\UsersTable;
use TestApp\Notification\AdminAlert;
use TestApp\Notification\PostPublished;
use TestApp\Notification\UserRegistered;

/**
 * NotificationTrait Test
 *
 * Tests all assertion methods provided by NotificationTrait
 *
 * @uses \Crustum\Notification\TestSuite\NotificationTrait
 */
class NotificationTraitTest extends TestCase
{
    use NotificationTrait;

    protected UsersTable $Users;

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
        $this->Users = $this->getTableLocator()->get('TestApp.Users');
    }

    /**
     * Test assertNotificationSent
     *
     * @return void
     */
    public function testAssertNotificationSent(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test Post'));

        $this->assertNotificationSent(PostPublished::class);
    }

    /**
     * Test assertNotificationNotSent
     *
     * @return void
     */
    public function testAssertNotificationNotSent(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test Post'));

        $this->assertNotificationNotSent(UserRegistered::class);
    }

    /**
     * Test assertNotificationCount
     *
     * @return void
     */
    public function testAssertNotificationCount(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user1 = $usersTable->get(1);
        $user2 = $usersTable->get(2);

        $usersTable->notify($user1, new PostPublished(1, 'Test Post'));
        $usersTable->notify($user2, new PostPublished(2, 'Another Post'));
        $usersTable->notify($user1, new AdminAlert('Alert Message'));

        $this->assertNotificationCount(3);
    }

    /**
     * Test assertNoNotificationsSent
     *
     * @return void
     */
    public function testAssertNoNotificationsSent(): void
    {
        $this->assertNoNotificationsSent();
    }

    /**
     * Test assertNotificationSentTo
     *
     * @return void
     */
    public function testAssertNotificationSentTo(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user1 = $usersTable->get(1);
        $user2 = $usersTable->get(2);

        $usersTable->notify($user1, new PostPublished(1, 'Test Post'));
        $usersTable->notify($user2, new AdminAlert('Alert'));

        $this->assertNotificationSentTo($user1, PostPublished::class);
        $this->assertNotificationSentTo($user2, AdminAlert::class);
    }

    /**
     * Test assertNotificationNotSentTo
     *
     * @return void
     */
    public function testAssertNotificationNotSentTo(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user1 = $usersTable->get(1);
        $user2 = $usersTable->get(2);

        $usersTable->notify($user1, new PostPublished(1, 'Test Post'));

        $this->assertNotificationNotSentTo($user2, PostPublished::class);
        $this->assertNotificationNotSentTo($user1, AdminAlert::class);
    }

    /**
     * Test assertNotificationSentToChannel
     *
     * @return void
     */
    public function testAssertNotificationSentToChannel(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test Post'));

        $this->assertNotificationSentToChannel('database', PostPublished::class);
        $this->assertNotificationSentToChannel('mail', PostPublished::class);
    }

    /**
     * Test assertOnDemandNotificationSent
     *
     * @return void
     */
    public function testAssertOnDemandNotificationSent(): void
    {
        $anonymous = NotificationManager::route('mail', 'admin@example.com');

        $anonymous->notify(new AdminAlert('Server Alert', 'critical'));

        $this->assertOnDemandNotificationSent(AdminAlert::class);
        $this->assertNotificationSentTo($anonymous, AdminAlert::class);
    }

    /**
     * Test assertNotificationSentTimes
     *
     * @return void
     */
    public function testAssertNotificationSentTimes(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user1 = $usersTable->get(1);
        $user2 = $usersTable->get(2);

        $usersTable->notify($user1, new PostPublished(1, 'First Post'));
        $usersTable->notify($user2, new PostPublished(2, 'Second Post'));
        $usersTable->notify($user1, new PostPublished(3, 'Third Post'));

        $this->assertNotificationSentTimes(PostPublished::class, 3);
        $this->assertNotificationSentTimes(AdminAlert::class, 0);
    }

    /**
     * Test assertNotificationSentToTimes
     *
     * @return void
     */
    public function testAssertNotificationSentToTimes(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'First Post'));
        $usersTable->notify($user, new PostPublished(2, 'Second Post'));
        $usersTable->notify($user, new AdminAlert('Alert'));

        $this->assertNotificationSentToTimes($user, PostPublished::class, 2);
        $this->assertNotificationSentToTimes($user, AdminAlert::class, 1);
    }

    /**
     * Test assertNotificationDataContains
     *
     * @return void
     */
    public function testAssertNotificationDataContains(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(123, 'My Great Post'));

        $this->assertNotificationDataContains(PostPublished::class, 'post_id', 123);
        $this->assertNotificationDataContains(PostPublished::class, 'post_title', 'My Great Post');
    }

    /**
     * Test getNotifications
     *
     * @return void
     */
    public function testGetNotifications(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test Post'));
        $usersTable->notify($user, new AdminAlert('Alert'));

        $notifications = $this->getNotifications();

        $this->assertCount(2, $notifications);
        $this->assertEquals(PostPublished::class, $notifications[0]['notification_class']);
        $this->assertEquals(AdminAlert::class, $notifications[1]['notification_class']);
    }

    /**
     * Test getNotificationsByClass
     *
     * @return void
     */
    public function testGetNotificationsByClass(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user1 = $usersTable->get(1);
        $user2 = $usersTable->get(2);

        $usersTable->notify($user1, new PostPublished(1, 'First'));
        $usersTable->notify($user2, new PostPublished(2, 'Second'));
        $usersTable->notify($user1, new AdminAlert('Alert'));

        $postNotifications = $this->getNotificationsByClass(PostPublished::class);
        $alertNotifications = $this->getNotificationsByClass(AdminAlert::class);

        $this->assertCount(2, $postNotifications);
        $this->assertCount(1, $alertNotifications);
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

        $usersTable->notify($user1, new PostPublished(1, 'First'));
        $usersTable->notify($user1, new PostPublished(2, 'Second'));
        $usersTable->notify($user2, new PostPublished(3, 'Third'));

        $user1Notifications = $this->getNotificationsFor($user1, PostPublished::class);
        $user2Notifications = $this->getNotificationsFor($user2, PostPublished::class);

        $this->assertCount(2, $user1Notifications);
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

        $usersTable->notify($user, new PostPublished(1, 'Post'));
        $usersTable->notify($user, new AdminAlert('Alert'));

        $databaseNotifications = $this->getNotificationsByChannel('database');
        $mailNotifications = $this->getNotificationsByChannel('mail');

        $this->assertCount(2, $databaseNotifications);
        $this->assertCount(1, $mailNotifications);
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

        $usersTable->notify($user, new PostPublished(1, 'Post'));
        $anonymous->notify(new AdminAlert('Server Down', 'critical'));

        $onDemandNotifications = $this->getOnDemandNotifications();

        $this->assertCount(1, $onDemandNotifications);
        $firstNotification = array_values($onDemandNotifications)[0];
        $this->assertEquals(AdminAlert::class, $firstNotification['notification_class']);
    }

    /**
     * Test multiple notifications to same user
     *
     * @return void
     */
    public function testMultipleNotificationsToSameUser(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'First Post'));
        $usersTable->notify($user, new PostPublished(2, 'Second Post'));
        $usersTable->notify($user, new PostPublished(3, 'Third Post'));

        $this->assertNotificationSentToTimes($user, PostPublished::class, 3);
        $this->assertNotificationCount(3);
    }

    /**
     * Test notifications to multiple users
     *
     * @return void
     */
    public function testNotificationsToMultipleUsers(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user1 = $usersTable->get(1);
        $user2 = $usersTable->get(2);
        $user3 = $usersTable->get(3);

        $usersTable->notify($user1, new PostPublished(1, 'Post'));
        $usersTable->notify($user2, new PostPublished(2, 'Post'));
        $usersTable->notify($user3, new PostPublished(3, 'Post'));

        $this->assertNotificationSentTo($user1, PostPublished::class);
        $this->assertNotificationSentTo($user2, PostPublished::class);
        $this->assertNotificationSentTo($user3, PostPublished::class);
        $this->assertNotificationCount(3);
    }

    /**
     * Test notification with sendNow
     *
     * @return void
     */
    public function testNotificationWithSendNow(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        NotificationManager::sendNow($user, new PostPublished(1, 'Test Post'));

        $this->assertNotificationSentTo($user, PostPublished::class);
        $this->assertNotificationCount(1);
    }

    /**
     * Test notification with specific channels in sendNow
     *
     * @return void
     */
    public function testNotificationWithSpecificChannels(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        NotificationManager::sendNow($user, new PostPublished(1, 'Test Post'), ['database']);

        $notifications = $this->getNotifications();
        $this->assertCount(1, $notifications);
        $this->assertEquals(['database'], $notifications[0]['channels']);
    }

    /**
     * Test notification metadata
     *
     * @return void
     */
    public function testNotificationMetadata(): void
    {
        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new PostPublished(1, 'Test Post'));

        $notifications = $this->getNotifications();
        $notification = $notifications[0];

        $this->assertEquals('TestApp\Model\Entity\User', $notification['notifiable_class']);
        $this->assertEquals('1', $notification['notifiable_id']);
        $this->assertEquals(PostPublished::class, $notification['notification_class']);
        $this->assertIsArray($notification['channels']);
        $this->assertContains('database', $notification['channels']);
        $this->assertContains('mail', $notification['channels']);
        $this->assertIsBool($notification['queued']);
        $this->assertIsInt($notification['timestamp']);
    }
}
