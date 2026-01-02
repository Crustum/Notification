<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Model\Trait;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Crustum\Notification\NotificationManager;
use Crustum\Notification\Test\TestCase\TestDatabaseNotification;
use TestApp\Model\Table\UsersTable;

/**
 * NotifiableTrait Test Case
 *
 * Tests the NotifiableTrait methods via UsersTable
 */
class NotifiableTraitTest extends TestCase
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
     * @var \TestApp\Model\Table\UsersTable
     */
    protected UsersTable $Users;

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

        /** @var \TestApp\Model\Table\UsersTable $usersTable */
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $this->Users = $usersTable;
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
        TableRegistry::getTableLocator()->clear();

        parent::tearDown();
    }

    /**
     * Test notify method sends notification
     *
     * @return void
     */
    public function testNotifySendsNotification(): void
    {
        $notification = new TestDatabaseNotification();

        $user = new Entity(['id' => 1]);
        $user->setSource('Users');

        $this->Users->notify($user, $notification);

        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');
        $saved = $notificationsTable->find()
            ->where(['type' => TestDatabaseNotification::class, 'foreign_key' => '1'])
            ->first();

        $this->assertNotNull($saved);
        $this->assertEquals('Users', $saved->model);
        $this->assertEquals('1', $saved->foreign_key);
    }

    /**
     * Test notifyNow method sends notification immediately
     *
     * @return void
     */
    public function testNotifyNowSendsNotificationImmediately(): void
    {
        $notification = new TestDatabaseNotification();

        $user = new Entity(['id' => 1]);
        $user->setSource('Users');

        $this->Users->notifyNow($user, $notification);

        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');
        $saved = $notificationsTable->find()
            ->where(['type' => TestDatabaseNotification::class, 'foreign_key' => '1'])
            ->first();

        $this->assertNotNull($saved);
        $this->assertEquals('Users', $saved->model);
    }

    /**
     * Test notifyNow method with specific channels
     *
     * @return void
     */
    public function testNotifyNowWithSpecificChannels(): void
    {
        $notification = new TestDatabaseNotification();

        $user = new Entity(['id' => 1]);
        $user->setSource('Users');

        $this->Users->notifyNow($user, $notification, ['database']);

        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');
        $saved = $notificationsTable->find()
            ->where(['type' => TestDatabaseNotification::class, 'foreign_key' => '1'])
            ->first();

        $this->assertNotNull($saved);
    }

    /**
     * Test routeNotificationFor returns association for database channel
     *
     * @return void
     */
    public function testRouteNotificationForDatabase(): void
    {
        $user = $this->Users->newEntity(['id' => 1, 'username' => 'testuser', 'email' => 'test@example.com']);

        $route = $this->Users->routeNotificationFor($user, 'database');

        $this->assertNotNull($route);
        $this->assertTrue($this->Users->hasAssociation('Notifications'));
    }

    /**
     * Test routeNotificationFor returns null for unknown channel
     *
     * @return void
     */
    public function testRouteNotificationForUnknownChannel(): void
    {
        $user = $this->Users->newEntity(['id' => 1, 'username' => 'testuser', 'email' => 'test@example.com']);

        $route = $this->Users->routeNotificationFor($user, 'unknown');

        $this->assertNull($route);
    }

    /**
     * Test routeNotificationFor uses entity method if exists
     *
     * @return void
     */
    public function testRouteNotificationForUsesEntityMethod(): void
    {
        $notification = new TestDatabaseNotification();
        $user = $this->Users->newEntity(['id' => 1, 'username' => 'testuser', 'email' => 'test@example.com']);

        $route = $this->Users->routeNotificationFor($user, 'mail', $notification);

        $this->assertEquals('test@example.com', $route);
    }

    /**
     * Test markNotificationAsRead marks notification as read
     *
     * @return void
     */
    public function testMarkNotificationAsRead(): void
    {
        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');
        $notification = $notificationsTable->newEntity([
            'type' => TestDatabaseNotification::class,
            'model' => 'Users',
            'foreign_key' => '1',
            'data' => ['message' => 'Test'],
        ]);
        $savedNotification = $notificationsTable->save($notification);
        $this->assertNotFalse($savedNotification, 'Notification should be saved');
        $notificationId = $savedNotification->get('id');

        $user = new Entity(['id' => 1]);
        $result = $this->Users->markNotificationAsRead($user, $notificationId);

        $this->assertTrue($result);

        $saved = $notificationsTable->get($notificationId);
        $this->assertNotNull($saved->get('read_at'));
    }

    /**
     * Test markAllNotificationsAsRead marks all notifications as read
     *
     * @return void
     */
    public function testMarkAllNotificationsAsRead(): void
    {
        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');

        $notification1 = $notificationsTable->newEntity([
            'type' => TestDatabaseNotification::class,
            'model' => 'Users',
            'foreign_key' => '1',
            'data' => ['message' => 'Test 1'],
        ]);
        $saved1 = $notificationsTable->save($notification1);
        $this->assertNotFalse($saved1, 'Notification 1 should be saved');

        $notification2 = $notificationsTable->newEntity([
            'type' => TestDatabaseNotification::class,
            'model' => 'Users',
            'foreign_key' => '1',
            'data' => ['message' => 'Test 2'],
        ]);
        $saved2 = $notificationsTable->save($notification2);
        $this->assertNotFalse($saved2, 'Notification 2 should be saved');

        $user = new Entity(['id' => 1]);
        $count = $this->Users->markAllNotificationsAsRead($user);

        $this->assertGreaterThanOrEqual(2, $count);

        $saved = $notificationsTable->find()
            ->where(['model' => 'Users', 'foreign_key' => '1', 'read_at IS NOT' => null])
            ->toArray();

        $this->assertGreaterThanOrEqual(2, count($saved));
    }

    /**
     * Test unreadNotifications returns query for unread notifications
     *
     * @return void
     */
    public function testUnreadNotifications(): void
    {
        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');

        $notification1 = $notificationsTable->newEntity([
            'type' => TestDatabaseNotification::class,
            'model' => 'Users',
            'foreign_key' => '1',
            'data' => ['message' => 'Test 1'],
        ]);
        $saved1 = $notificationsTable->save($notification1);
        $this->assertNotFalse($saved1, 'Notification 1 should be saved');
        $unreadId = $saved1->get('id');

        $notification2 = $notificationsTable->newEntity([
            'type' => TestDatabaseNotification::class,
            'model' => 'Users',
            'foreign_key' => '1',
            'data' => ['message' => 'Test 2'],
            'read_at' => new DateTime(),
        ]);
        $saved2 = $notificationsTable->save($notification2);
        $this->assertNotFalse($saved2, 'Notification 2 should be saved');

        $user = new Entity(['id' => 1]);
        $query = $this->Users->unreadNotifications($user);
        $results = $query->toArray();

        $unreadIds = array_map(fn($n) => $n->get('id'), $results);
        $this->assertContains($unreadId, $unreadIds);
    }

    /**
     * Test readNotifications returns query for read notifications
     *
     * @return void
     */
    public function testReadNotifications(): void
    {
        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');

        $notification1 = $notificationsTable->newEntity([
            'type' => TestDatabaseNotification::class,
            'model' => 'Users',
            'foreign_key' => '1',
            'data' => ['message' => 'Test 1'],
        ]);
        $saved1 = $notificationsTable->save($notification1);
        $this->assertNotFalse($saved1, 'Notification 1 should be saved');

        $notification2 = $notificationsTable->newEntity([
            'type' => TestDatabaseNotification::class,
            'model' => 'Users',
            'foreign_key' => '1',
            'data' => ['message' => 'Test 2'],
            'read_at' => new DateTime(),
        ]);
        $saved2 = $notificationsTable->save($notification2);
        $this->assertNotFalse($saved2, 'Notification 2 should be saved');
        $readId = $saved2->get('id');

        $user = new Entity(['id' => 1]);
        $query = $this->Users->readNotifications($user);
        $results = $query->toArray();

        $readIds = array_map(fn($n) => $n->get('id'), $results);
        $this->assertContains($readId, $readIds);
    }
}
