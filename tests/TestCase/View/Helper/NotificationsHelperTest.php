<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\View\Helper;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Crustum\Notification\Model\Entity\Notification;
use Crustum\Notification\Test\TestCase\TestNotification;
use Crustum\Notification\View\Helper\NotificationsHelper;

/**
 * NotificationsHelper Test Case
 *
 * Tests the NotificationsHelper methods
 */
class NotificationsHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Crustum\Notification\View\Helper\NotificationsHelper
     */
    protected NotificationsHelper $Helper;

    /**
     * View instance
     *
     * @var \Cake\View\View
     */
    protected View $View;

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

        $this->View = new View();
        $this->Helper = new NotificationsHelper($this->View);
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
     * Test getNotificationTitle returns title from data
     *
     * @return void
     */
    public function testGetNotificationTitleReturnsTitleFromData(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => ['title' => 'Test Title'],
        ]);

        $result = $this->Helper->getNotificationTitle($notification);

        $this->assertEquals('Test Title', $result);
    }

    /**
     * Test getNotificationTitle returns title from class name when no data title
     *
     * @return void
     */
    public function testGetNotificationTitleReturnsTitleFromClassName(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => [],
        ]);

        $result = $this->Helper->getNotificationTitle($notification);

        $this->assertStringContainsString('Test', $result);
    }

    /**
     * Test getNotificationTitle handles class with getTitle method
     *
     * @return void
     */
    public function testGetNotificationTitleHandlesClassWithGetTitleMethod(): void
    {
        $notification = new Notification([
            'type' => TestNotificationWithTitle::class,
            'data' => [],
        ]);

        $result = $this->Helper->getNotificationTitle($notification);

        $this->assertEquals('Custom Title', $result);
    }

    /**
     * Test getNotificationTitle handles non-existent class gracefully
     *
     * @return void
     */
    public function testGetNotificationTitleHandlesNonExistentClass(): void
    {
        $notification = new Notification([
            'type' => 'NonExistent\Class\Name',
            'data' => ['title' => 'Fallback Title'],
        ]);

        $result = $this->Helper->getNotificationTitle($notification);

        $this->assertEquals('Fallback Title', $result);
    }

    /**
     * Test getNotificationMessage returns message from data
     *
     * @return void
     */
    public function testGetNotificationMessageReturnsMessageFromData(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => ['message' => 'Test Message'],
        ]);

        $result = $this->Helper->getNotificationMessage($notification);

        $this->assertEquals('Test Message', $result);
    }

    /**
     * Test getNotificationMessage returns title when message not in data
     *
     * @return void
     */
    public function testGetNotificationMessageReturnsTitleWhenMessageNotInData(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => ['title' => 'Test Title'],
        ]);

        $result = $this->Helper->getNotificationMessage($notification);

        $this->assertEquals('Test Title', $result);
    }

    /**
     * Test getNotificationMessage returns generic message when no data
     *
     * @return void
     */
    public function testGetNotificationMessageReturnsGenericMessageWhenNoData(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => [],
        ]);

        $result = $this->Helper->getNotificationMessage($notification);

        $this->assertEquals(__('You have a new notification'), $result);
    }

    /**
     * Test getNotificationTypes returns distinct types from database
     *
     * @return void
     */
    public function testGetNotificationTypesReturnsDistinctTypes(): void
    {
        $notificationsTable = TableRegistry::getTableLocator()->get('Crustum/Notification.Notifications');

        $notification1 = $notificationsTable->newEntity([
            'type' => TestNotification::class,
            'model' => 'Users',
            'foreign_key' => '1',
            'data' => ['message' => 'Test 1'],
        ]);
        $notificationsTable->save($notification1);

        $notification2 = $notificationsTable->newEntity([
            'type' => TestNotification::class,
            'model' => 'Users',
            'foreign_key' => '2',
            'data' => ['message' => 'Test 2'],
        ]);
        $notificationsTable->save($notification2);

        $result = $this->Helper->getNotificationTypes();

        $this->assertArrayHasKey(TestNotification::class, $result);
    }

    /**
     * Test getNotificationIcon returns icon from data
     *
     * @return void
     */
    public function testGetNotificationIconReturnsIconFromData(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => ['icon' => 'star'],
        ]);

        $result = $this->Helper->getNotificationIcon($notification);

        $this->assertEquals('star', $result);
    }

    /**
     * Test getNotificationIcon returns default bell when no icon in data
     *
     * @return void
     */
    public function testGetNotificationIconReturnsDefaultBellWhenNoIconInData(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => [],
        ]);

        $result = $this->Helper->getNotificationIcon($notification);

        $this->assertEquals('bell', $result);
    }

    /**
     * Test getNotificationIcon handles class with getIcon method
     *
     * @return void
     */
    public function testGetNotificationIconHandlesClassWithGetIconMethod(): void
    {
        $notification = new Notification([
            'type' => TestNotificationWithIcon::class,
            'data' => [],
        ]);

        $result = $this->Helper->getNotificationIcon($notification);

        $this->assertEquals('custom-icon', $result);
    }

    /**
     * Test getNotificationIcon handles non-existent class gracefully
     *
     * @return void
     */
    public function testGetNotificationIconHandlesNonExistentClass(): void
    {
        $notification = new Notification([
            'type' => 'NonExistent\Class\Name',
            'data' => [],
        ]);

        $result = $this->Helper->getNotificationIcon($notification);

        $this->assertEquals('bell', $result);
    }

    /**
     * Test formatNotificationData returns empty string when data is empty
     *
     * @return void
     */
    public function testFormatNotificationDataReturnsEmptyStringWhenDataIsEmpty(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => [],
        ]);

        $result = $this->Helper->formatNotificationData($notification);

        $this->assertEquals('', $result);
    }

    /**
     * Test formatNotificationData formats data as HTML
     *
     * @return void
     */
    public function testFormatNotificationDataFormatsDataAsHtml(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => [
                'title' => 'Test Title',
                'message' => 'Test Message',
                'custom_field' => 'Custom Value',
            ],
        ]);

        $result = $this->Helper->formatNotificationData($notification);

        $this->assertStringContainsString('<dl class="notification-data">', $result);
        $this->assertStringContainsString('<dt>Custom field</dt>', $result);
        $this->assertStringContainsString('<dd>Custom Value</dd>', $result);
        $this->assertStringNotContainsString('Test Title', $result);
        $this->assertStringNotContainsString('Test Message', $result);
    }

    /**
     * Test formatNotificationData escapes HTML in values
     *
     * @return void
     */
    public function testFormatNotificationDataEscapesHtmlInValues(): void
    {
        $notification = new Notification([
            'type' => TestNotification::class,
            'data' => [
                'html_field' => '<script>alert("xss")</script>',
            ],
        ]);

        $result = $this->Helper->formatNotificationData($notification);

        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }
}
