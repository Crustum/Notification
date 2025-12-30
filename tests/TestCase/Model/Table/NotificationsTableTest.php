<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Model\Table\NotificationsTable;

/**
 * NotificationsTable Test Case
 *
 * Tests custom finders and table functionality
 */
class NotificationsTableTest extends TestCase
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
     * Test subject
     *
     * @var \Crustum\Notification\Model\Table\NotificationsTable
     */
    protected NotificationsTable $Notifications;

    /**
     * Set up test case
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        /** @var \Crustum\Notification\Model\Table\NotificationsTable $notificationsTable */
        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');
        $this->Notifications = $notificationsTable;
    }

    /**
     * Tear down test case
     *
     * @return void
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->clear();

        parent::tearDown();
    }

    /**
     * Test findRead custom finder returns only read notifications
     *
     * @return void
     */
    public function testFindRead(): void
    {
        $query = $this->Notifications->find('read');
        $results = $query->all()->extract('id')->toArray();

        $this->assertCount(1, $results);
        $this->assertContains('notification-read-1', $results);
    }

    /**
     * Test findUnread custom finder returns only unread notifications
     *
     * @return void
     */
    public function testFindUnread(): void
    {
        $query = $this->Notifications->find('unread');
        $results = $query->all()->extract('id')->toArray();

        $this->assertCount(2, $results);
        $this->assertContains('notification-unread-1', $results);
        $this->assertContains('notification-unread-2', $results);
    }

    /**
     * Test findForModel finder with model parameter only
     *
     * @return void
     */
    public function testFindForModelWithModelOnly(): void
    {
        $query = $this->Notifications->find('forModel', model: 'Users');
        $results = $query->all();

        $this->assertCount(2, $results);
        foreach ($results as $notification) {
            $this->assertEquals('Users', $notification->model);
        }
    }

    /**
     * Test findForModel finder with both model and foreign_key parameters
     *
     * @return void
     */
    public function testFindForModelWithModelAndForeignKey(): void
    {
        $query = $this->Notifications->find(
            'forModel',
            model: 'Users',
            foreign_key: 'user-uuid-1',
        );
        $results = $query->all();

        $this->assertCount(2, $results);
        foreach ($results as $notification) {
            $this->assertEquals('Users', $notification->model);
            $this->assertEquals('user-uuid-1', $notification->foreign_key);
        }
    }

    /**
     * Test markAsRead marks notification as read
     *
     * @return void
     */
    public function testMarkAsRead(): void
    {
        $notification = $this->Notifications->find('unread')->first();
        $this->assertNull($notification->read_at);

        $success = $this->Notifications->markAsRead($notification->id);

        $this->assertTrue($success);

        $notification = $this->Notifications->get($notification->id);
        $this->assertNotNull($notification->read_at);
    }

    /**
     * Test markAsRead returns false if already read
     *
     * @return void
     */
    public function testMarkAsReadReturnsFalseIfAlreadyRead(): void
    {
        $notification = $this->Notifications->find('read')->first();

        $success = $this->Notifications->markAsRead($notification->id);

        $this->assertFalse($success);
    }

    /**
     * Test markManyAsRead marks multiple notifications
     *
     * @return void
     */
    public function testMarkManyAsRead(): void
    {
        $notifications = $this->Notifications->find('unread')->all();
        $ids = $notifications->extract('id')->toArray();

        $count = $this->Notifications->markManyAsRead($ids);

        $this->assertGreaterThan(0, $count);

        foreach ($ids as $id) {
            $notification = $this->Notifications->get($id);
            $this->assertNotNull($notification->read_at);
        }
    }

    /**
     * Test markAllAsRead marks all for specific entity
     *
     * @return void
     */
    public function testMarkAllAsRead(): void
    {
        $unreadBefore = $this->Notifications
            ->find('forModel', model: 'Users', foreign_key: 'user-uuid-1')
            ->find('unread')
            ->count();

        if ($unreadBefore === 0) {
            $this->markTestSkipped('No unread notifications in fixture for user-uuid-1');
        }

        $count = $this->Notifications->markAllAsRead('Users', 'user-uuid-1');

        $this->assertEquals($unreadBefore, $count);

        $unreadAfter = $this->Notifications
            ->find('forModel', model: 'Users', foreign_key: 'user-uuid-1')
            ->find('unread')
            ->count();

        $this->assertEquals(0, $unreadAfter);
    }

    /**
     * Test markAsUnread marks notification as unread
     *
     * @return void
     */
    public function testMarkAsUnread(): void
    {
        $notification = $this->Notifications->find('read')->first();
        $this->assertNotNull($notification->read_at);

        $success = $this->Notifications->markAsUnread($notification->id);

        $this->assertTrue($success);

        $notification = $this->Notifications->get($notification->id);
        $this->assertNull($notification->read_at);
    }
}
