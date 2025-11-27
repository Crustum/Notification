<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Integration;

use Cake\TestSuite\TestCase;
use Crustum\Notification\TestSuite\NotificationTrait;
use TestApp\Notification\PostPublished;

/**
 * Post Notification Integration Test
 *
 * Tests that notifications are sent when posts are published
 *
 * @uses \Crustum\Notification\TestSuite\NotificationTrait
 */
class PostNotificationTest extends TestCase
{
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
     * Test setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test notification sent when post is published
     *
     * @return void
     */
    public function testNotificationSentWhenPostPublished(): void
    {
        $postsTable = $this->getTableLocator()->get('TestApp.Posts');
        $usersTable = $this->getTableLocator()->get('TestApp.Users');

        $post = $postsTable->get(1);
        $user = $usersTable->get($post->user_id);

        $post->published = true;
        $postsTable->save($post);

        $this->assertNotificationSentTo($user, PostPublished::class);
        $this->assertNotificationCount(1);
    }

    /**
     * Test notification not sent when post not published
     *
     * @return void
     */
    public function testNotificationNotSentWhenPostNotPublished(): void
    {
        $postsTable = $this->getTableLocator()->get('TestApp.Posts');
        $post = $postsTable->get(1);

        $post->title = 'Updated Title';
        $postsTable->save($post);

        $this->assertNoNotificationsSent();
    }

    /**
     * Test notification contains correct post data
     *
     * @return void
     */
    public function testNotificationContainsPostData(): void
    {
        $postsTable = $this->getTableLocator()->get('TestApp.Posts');
        $post = $postsTable->get(1);

        $post->published = true;
        $postsTable->save($post);

        $this->assertNotificationDataContains(PostPublished::class, 'post_id', 1);
        $this->assertNotificationDataContains(PostPublished::class, 'post_title', 'First Post');
    }

    /**
     * Test notification sent to correct user
     *
     * @return void
     */
    public function testNotificationSentToPostOwner(): void
    {
        $postsTable = $this->getTableLocator()->get('TestApp.Posts');
        $usersTable = $this->getTableLocator()->get('TestApp.Users');

        $post = $postsTable->get(1);
        $owner = $usersTable->get($post->user_id);
        $otherUser = $usersTable->get(2);

        $post->published = true;
        $postsTable->save($post);

        $this->assertNotificationSentTo($owner, PostPublished::class);
        $this->assertNotificationNotSentTo($otherUser, PostPublished::class);
    }

    /**
     * Test notification sent through correct channels
     *
     * @return void
     */
    public function testNotificationSentThroughCorrectChannels(): void
    {
        $postsTable = $this->getTableLocator()->get('TestApp.Posts');
        $post = $postsTable->get(1);

        $post->published = true;
        $postsTable->save($post);

        $this->assertNotificationSentToChannel('database', PostPublished::class);
        $this->assertNotificationSentToChannel('mail', PostPublished::class);
    }

    /**
     * Test publishing multiple posts sends multiple notifications
     *
     * @return void
     */
    public function testMultiplePostPublishSendsMultipleNotifications(): void
    {
        $postsTable = $this->getTableLocator()->get('TestApp.Posts');
        $post1 = $postsTable->get(1);
        $post3 = $postsTable->get(3);

        $post1->published = true;
        $postsTable->save($post1);

        $post3->published = true;
        $postsTable->save($post3);

        $this->assertNotificationSentTimes(PostPublished::class, 2);
    }
}
