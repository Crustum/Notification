<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Integration;

use Cake\TestSuite\TestCase;
use Crustum\Notification\TestSuite\NotificationTrait;
use TestApp\Model\Table\PostsTable;
use TestApp\Model\Table\UsersTable;
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

    protected PostsTable $Posts;

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
        /** @var \TestApp\Model\Table\PostsTable $postsTable */
        $postsTable = $this->getTableLocator()->get('Posts');
        $this->Posts = $postsTable;
        /** @var \TestApp\Model\Table\UsersTable $usersTable */
        $usersTable = $this->getTableLocator()->get('Users');
        $this->Users = $usersTable;
    }

    /**
     * Test notification sent when post is published
     *
     * @return void
     */
    public function testNotificationSentWhenPostPublished(): void
    {
        $post = $this->Posts->get(1);
        $user = $this->Users->get($post->user_id);

        $post->published = true;
        $this->Posts->save($post);

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
        $post = $this->Posts->get(1);

        $post->title = 'Updated Title';
        $this->Posts->save($post);

        $this->assertNoNotificationsSent();
    }

    /**
     * Test notification contains correct post data
     *
     * @return void
     */
    public function testNotificationContainsPostData(): void
    {
        $post = $this->Posts->get(1);

        $post->published = true;
        $this->Posts->save($post);

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
        $post = $this->Posts->get(1);
        $owner = $this->Users->get($post->user_id);
        $otherUser = $this->Users->get(2);

        $post->published = true;
        $this->Posts->save($post);

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
        $post = $this->Posts->get(1);

        $post->published = true;
        $this->Posts->save($post);

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
        $post1 = $this->Posts->get(1);
        $post3 = $this->Posts->get(3);

        $post1->published = true;
        $this->Posts->save($post1);

        $post3->published = true;
        $this->Posts->save($post3);

        $this->assertNotificationSentTimes(PostPublished::class, 2);
    }
}
