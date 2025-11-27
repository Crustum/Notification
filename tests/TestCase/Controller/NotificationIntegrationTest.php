<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Crustum\Notification\TestSuite\NotificationTrait;
use TestApp\Notification\PostPublished;

/**
 * Notification Integration Test
 *
 * Tests that NotificationTrait works correctly with IntegrationTestTrait
 * for controller/integration testing scenarios where controllers send notifications.
 */
class NotificationIntegrationTest extends TestCase
{
    use IntegrationTestTrait;
    use NotificationTrait;

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
     * Test controller action notifications are captured
     *
     * @return void
     */
    public function testControllerActionNotificationCaptured(): void
    {
        $this->post('/posts/notify', [
            'user_id' => 1,
            'post_id' => 100,
            'title' => 'Test Post Title',
        ]);

        $this->assertResponseOk();

        $this->assertNotificationSent(PostPublished::class);

        $usersTable = $this->getTableLocator()->get('TestApp.Users');
        $user = $usersTable->get(1);

        $this->assertNotificationSentTo($user, PostPublished::class);
        $this->assertNotificationSentToChannel('database', PostPublished::class);
        $this->assertNotificationSentToChannel('mail', PostPublished::class);
    }

    /**
     * Test controller broadcasts multiple notifications
     *
     * @return void
     */
    public function testMultipleNotificationsFromController(): void
    {
        $this->post('/posts/notify', [
            'user_id' => 1,
            'post_id' => 100,
            'title' => 'First Post',
        ]);

        $this->assertNotificationCount(1);

        $this->post('/posts/notify', [
            'user_id' => 2,
            'post_id' => 200,
            'title' => 'Second Post',
        ]);

        $this->assertNotificationCount(2);
        $this->assertNotificationSentTimes(PostPublished::class, 2);
    }

    /**
     * Test notifications isolated between requests
     *
     * @return void
     */
    public function testNotificationsIsolatedBetweenTests(): void
    {
        $this->assertNoNotificationsSent();

        $this->post('/posts/notify', [
            'user_id' => 1,
            'post_id' => 100,
            'title' => 'Test Post',
        ]);

        $this->assertNotificationCount(1);
    }

    /**
     * Test notification content from controller
     *
     * @return void
     */
    public function testNotificationContentFromController(): void
    {
        $this->post('/posts/notify', [
            'user_id' => 1,
            'post_id' => 999,
            'title' => 'Amazing Post',
        ]);

        $notifications = $this->getNotificationsByClass(PostPublished::class);
        $this->assertCount(1, $notifications);

        $this->assertNotificationDataContains(PostPublished::class, 'post_id', 999);
    }
}
