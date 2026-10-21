<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Panel;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;
use Crustum\Notification\Panel\NotificationsPanel;
use RuntimeException;
use TestApp\Notification\DatabaseOnlyNotification;
use TestApp\Notification\SimpleNotification;
use Throwable;

/**
 * NotificationsPanel Test Case
 */
class NotificationsPanelTest extends TestCase
{
    /**
     * @var \Crustum\Notification\Panel\NotificationsPanel
     */
    protected NotificationsPanel $panel;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        NotificationsPanel::clear();
        $this->panel = new NotificationsPanel();
        EventManager::instance()->on($this->panel);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        EventManager::instance()->off($this->panel);
        NotificationsPanel::clear();
        parent::tearDown();
    }

    /**
     * Toolbar adapter contract stays intact.
     *
     * @return void
     */
    public function testPanelContract(): void
    {
        $this->assertSame('Notifications', $this->panel->title());
        $this->assertSame('Crustum/Notification.notifications_panel', $this->panel->elementName());
        $this->assertSame('', $this->panel->summary());
        $this->assertSame(
            ['groups' => [], 'summary' => ['count' => 0, 'attention' => 0], 'facets' => ['to' => [], 'class' => []]],
            $this->panel->data(),
        );
    }

    /**
     * Sent delivery groups by notifiable plus notification class.
     *
     * @return void
     */
    public function testSentDeliveryGroups(): void
    {
        $notifiable = new Entity(['id' => 7, 'name' => 'Ann'], ['markNew' => false]);
        $notification = new SimpleNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'mail');
        $this->dispatch('Model.Notification.sent', $notifiable, $notification, 'mail', ['id' => 'm-1']);

        $this->assertSame('1 notifications', $this->panel->summary());
        $data = $this->panel->data();

        $this->assertCount(1, $data['groups']);
        $group = $data['groups'][0];
        $this->assertSame('sent', $group['status']);
        $this->assertSame('Sent', $group['status_label']);
        $this->assertSame(1, $group['delivery_count']);
        $this->assertSame(['mail'], $group['channels']);
        $this->assertSame('Ann', $group['recipient']['name']);
        $this->assertSame('7', $group['recipient']['id']);
        $this->assertSame(['post_id' => 9], $group['notification_data']);
        $this->assertStringContainsString('simplenotification', $group['search']);
        $delivery = $group['deliveries'][0];
        $this->assertSame('mail', $delivery['channel']);
        $this->assertSame('sent', $delivery['status']);
        $this->assertSame(['id' => 'm-1'], $delivery['response']);
        $this->assertSame(['count' => 1, 'attention' => 0], $data['summary']);
    }

    /**
     * Mixed outcomes report a partial group needing attention.
     *
     * @return void
     */
    public function testMixedOutcomesReportPartial(): void
    {
        $notifiable = new Entity(['id' => 3], ['markNew' => false]);
        $notification = new SimpleNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'mail');
        $this->dispatch('Model.Notification.sent', $notifiable, $notification, 'mail');
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'database');
        $this->dispatch(
            'Model.Notification.failed',
            $notifiable,
            $notification,
            'database',
            new RuntimeException('connection refused'),
        );

        $data = $this->panel->data();

        $this->assertCount(1, $data['groups']);
        $group = $data['groups'][0];
        $this->assertSame('partial', $group['status']);
        $this->assertSame('Needs attention', $group['status_label']);
        $this->assertSame(1, $group['sent_count']);
        $this->assertSame(1, $group['failed_count']);
        $this->assertSame(1, $data['summary']['attention']);
        $this->assertStringContainsString('connection refused', $group['search']);
    }

    /**
     * Skipped and pending deliveries keep honest statuses.
     *
     * @return void
     */
    public function testSkippedAndPendingStatuses(): void
    {
        $notifiable = new Entity(['id' => 1], ['markNew' => false]);
        $skipped = new SimpleNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $skipped, 'mail');
        $this->dispatch('Model.Notification.skipped', $notifiable, $skipped, 'mail');
        $pending = new SimpleNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $pending, 'mail');

        $data = $this->panel->data();

        $this->assertCount(1, $data['groups']);
        $group = $data['groups'][0];
        $statuses = array_column($group['deliveries'], 'status');
        $this->assertContains('skipped', $statuses);
        $this->assertContains('pending', $statuses);
        $this->assertSame('pending', $group['status']);
    }

    /**
     * Fully skipped groups report their own status.
     *
     * @return void
     */
    public function testAllSkippedGroup(): void
    {
        $notifiable = new Entity(['id' => 2], ['markNew' => false]);
        $notification = new SimpleNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'mail');
        $this->dispatch('Model.Notification.skipped', $notifiable, $notification, 'mail');

        $data = $this->panel->data();

        $this->assertSame('skipped', $data['groups'][0]['status']);
        $this->assertSame('Skipped', $data['groups'][0]['status_label']);
        $this->assertSame(1, $data['summary']['attention']);
    }

    /**
     * Anonymous routes resolve destinations from channel routes.
     *
     * @return void
     */
    public function testAnonymousDestination(): void
    {
        $notifiable = (new AnonymousNotifiable())->route('mail', 'ops@example.com');
        $notification = new SimpleNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'mail');
        $this->dispatch('Model.Notification.sent', $notifiable, $notification, 'mail');

        $data = $this->panel->data();

        $group = $data['groups'][0];
        $this->assertSame('ops@example.com', $group['recipient']['label']);
        $this->assertSame(['mail' => 'ops@example.com'], $group['routes']);
    }

    /**
     * Element renders groups with list hooks and detail panes.
     *
     * Toolbar kit elements resolve only inside a host app with both
     * plugins loaded, so the render test stubs those calls and asserts
     * our markup plus the integration points.
     *
     * @return void
     */
    public function testElementRendersGroups(): void
    {
        $notifiable = new Entity(['id' => 7, 'name' => 'Ann'], ['markNew' => false]);
        $notification = new SimpleNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'mail');
        $this->dispatch('Model.Notification.sent', $notifiable, $notification, 'mail');
        $data = $this->panel->data();

        $view = new class (new ServerRequest()) extends View {
            /**
             * Stubs toolbar kit elements unavailable in isolation.
             *
             * @param string $name Element name.
             * @param array<string, mixed> $data Element variables.
             * @param array<string, mixed> $options Element options.
             * @return string
             */
            public function element(string $name, array $data = [], array $options = []): string
            {
                if ($name === 'Crustum/DebugBar.ui/inspector_workspace') {
                    return ($data['list'] ?? '') . ($data['detail'] ?? '');
                }

                if ($name === 'Crustum/DebugBar.ui/inspector_list_panel') {
                    return ($data['controls'] ?? '') . ($data['body'] ?? '');
                }

                if (str_starts_with($name, 'Crustum/DebugBar.')) {
                    return '<!--kit:' . $name . '-->';
                }

                return parent::element($name, $data, $options);
            }
        };
        $html = $view->element('Crustum/Notification.notifications_panel', $data);

        $this->assertStringContainsString('data-dk-notifications-panel', $html);
        $this->assertStringContainsString('data-dk-notification-entry', $html);
        $this->assertStringContainsString('data-dk-notification-detail="notification-1"', $html);
        $this->assertStringContainsString('SimpleNotification', $html);
        $this->assertStringContainsString('Ann', $html);
        $this->assertStringContainsString('data-dk-tab="notification-1:payload"', $html);
        $this->assertStringContainsString('data-dk-selection-config', $html);
        $this->assertStringContainsString('data-dk-notification-entry', $html);
        $this->assertStringContainsString('<!--kit:Crustum/DebugBar.ui/icon-->', $html);
        $this->assertStringContainsString('<!--kit:Crustum/DebugBar.ui/debug_tree-->', $html);
        $this->assertStringContainsString('<!--kit:Crustum/DebugBar.ui/tree_filter-->', $html);
        $this->assertStringContainsString('data-dk-tree-scope', $html);
        $this->assertStringContainsString('<!--kit:Crustum/DebugBar.ui/source_ref-->', $html);
    }

    /**
     * Database channel responses export to expandable nodes, not markers.
     *
     * Same pipeline as the Variables inspector: entities keep their
     * class plus properties so inner objects stay visible.
     *
     * @return void
     */
    public function testDatabaseResponseShapesToExpandableNodes(): void
    {
        $notifiable = new Entity(['id' => 5], ['markNew' => false]);
        $notification = new SimpleNotification();
        $record = new Entity(['id' => 42, 'type' => 'posts'], ['markNew' => false, 'source' => 'Notifications']);
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'database');
        $this->dispatch('Model.Notification.sent', $notifiable, $notification, 'database', $record);

        $data = $this->panel->data();
        $delivery = $data['groups'][0]['deliveries'][0];

        $this->assertSame('Database record #42', $delivery['response_summary']);
        $this->assertSame(Entity::class, $delivery['response']['__class']);
        $this->assertSame(42, $delivery['response']['id']);
    }

    /**
     * Inner objects survive capture instead of collapsing to class names.
     *
     * @return void
     */
    public function testInnerObjectsStayExpandable(): void
    {
        $notifiable = new Entity(['id' => 5], ['markNew' => false]);
        $notification = new SimpleNotification();
        $record = new Entity(
            ['id' => 7, 'created' => new DateTime('2026-01-02 03:04:05')],
            ['markNew' => false, 'source' => 'Notifications'],
        );
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'database');
        $this->dispatch('Model.Notification.sent', $notifiable, $notification, 'database', $record);

        $data = $this->panel->data();
        $response = $data['groups'][0]['deliveries'][0]['response'];

        $this->assertIsArray($response['created']);
        $this->assertSame(DateTime::class, $response['created']['__class']);
    }

    /**
     * Channel payloads back the application payload when toArray is empty.
     *
     * @return void
     */
    public function testPayloadFallsBackToDatabaseMessage(): void
    {
        $notifiable = new Entity(['id' => 1], ['markNew' => false]);
        $notification = new DatabaseOnlyNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'database');
        $this->dispatch('Model.Notification.sent', $notifiable, $notification, 'database');

        $data = $this->panel->data();

        $this->assertSame(['post_id' => 3], $data['groups'][0]['notification_data']);
    }

    /**
     * Callsite skips vendor frames and filter facets collect tokens.
     *
     * @return void
     */
    public function testCallsiteAndFacets(): void
    {
        $notifiable = new Entity(['id' => 7, 'name' => 'Ann'], ['markNew' => false]);
        $notification = new SimpleNotification();
        $this->dispatch('Model.Notification.sending', $notifiable, $notification, 'mail');
        $this->dispatch('Model.Notification.sent', $notifiable, $notification, 'mail');

        $data = $this->panel->data();
        $group = $data['groups'][0];

        $this->assertStringEndsWith('NotificationsPanelTest.php', (string)($group['callsite']['file'] ?? ''));
        $this->assertStringContainsString('to:ann', (string)($group['filter'] ?? ''));
        $this->assertStringContainsString('class:simplenotification', (string)($group['filter'] ?? ''));
        $this->assertSame('Ann', $data['facets']['to']['to:ann'] ?? null);
        $this->assertSame(
            'SimpleNotification',
            $data['facets']['class']['class:simplenotification'] ?? null,
        );
    }

    /**
     * Dispatches a notification lifecycle event on the global manager.
     *
     * @param string $name Event name.
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable.
     * @param \Crustum\Notification\Notification $notification Notification.
     * @param string $channel Channel name.
     * @param mixed $extra Response or exception payload.
     * @return void
     */
    protected function dispatch(
        string $name,
        EntityInterface|AnonymousNotifiable $notifiable,
        Notification $notification,
        string $channel,
        mixed $extra = null,
    ): void {
        $payload = ['notifiable' => $notifiable, 'notification' => $notification, 'channel' => $channel];
        if ($extra instanceof Throwable) {
            $payload['exception'] = $extra;
        } elseif ($name === 'Model.Notification.sent') {
            $payload['response'] = $extra;
        }

        EventManager::instance()->dispatch(new Event($name, null, $payload));
    }
}
