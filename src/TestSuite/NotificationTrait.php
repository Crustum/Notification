<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\NotificationManager;
use Crustum\Notification\TestSuite\Constraint\Notification\NoNotificationSent;
use Crustum\Notification\TestSuite\Constraint\Notification\NotificationCount;
use Crustum\Notification\TestSuite\Constraint\Notification\NotificationDataContains;
use Crustum\Notification\TestSuite\Constraint\Notification\NotificationSent;
use Crustum\Notification\TestSuite\Constraint\Notification\NotificationSentTimes;
use Crustum\Notification\TestSuite\Constraint\Notification\NotificationSentTo;
use Crustum\Notification\TestSuite\Constraint\Notification\NotificationSentToChannel;
use Crustum\Notification\TestSuite\Constraint\Notification\OnDemandNotificationSent;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

/**
 * Notification Trait
 *
 * Make assertions on notifications sent through TestNotificationSender.
 *
 * After adding the trait to your test case, all notifications will be captured
 * instead of being sent, allowing you to make assertions.
 *
 * Usage:
 * ```
 * class MyTest extends TestCase
 * {
 *     use NotificationTrait;
 *
 *     public function testNotificationSent(): void
 *     {
 *         $user = $this->Users->get(1);
 *         $user->notify(new InvoicePaid());
 *
 *         $this->assertNotificationSentTo($user, InvoicePaid::class);
 *     }
 * }
 * ```
 */
trait NotificationTrait
{
    /**
     * Setup test notification sender
     *
     * Replaces the notification sender with TestNotificationSender
     * to capture notifications instead of sending them.
     *
     * @return void
     */
    #[Before]
    public function setupNotificationSender(): void
    {
        TestNotificationSender::replaceAllSenders();
    }

    /**
     * Cleanup notifications
     *
     * Clears all captured notifications after each test.
     *
     * @return void
     */
    #[After]
    public function cleanupNotificationTrait(): void
    {
        TestNotificationSender::clearNotifications();
        NotificationManager::resetSender();
    }

    /**
     * Assert a notification of a specific class was sent
     *
     * @param string $notificationClass Notification class name
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationSent(string $notificationClass, string $message = ''): void
    {
        $this->assertThat($notificationClass, new NotificationSent(), $message);
    }

    /**
     * Assert a notification at a specific index was sent
     *
     * @param int $at Notification index (0-based)
     * @param string $notificationClass Notification class name
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationSentAt(int $at, string $notificationClass, string $message = ''): void
    {
        $this->assertThat($notificationClass, new NotificationSent($at), $message);
    }

    /**
     * Assert a notification was not sent
     *
     * @param string $notificationClass Notification class name
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationNotSent(string $notificationClass, string $message = ''): void
    {
        $notifications = TestNotificationSender::getNotificationsByClass($notificationClass);
        $this->assertEmpty(
            $notifications,
            $message ?: "Notification {$notificationClass} was sent unexpectedly",
        );
    }

    /**
     * Assert no notifications were sent
     *
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNoNotificationsSent(string $message = ''): void
    {
        $this->assertThat(null, new NoNotificationSent(), $message);
    }

    /**
     * Assert a specific count of notifications were sent
     *
     * @param int $count Expected notification count
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationCount(int $count, string $message = ''): void
    {
        $this->assertThat($count, new NotificationCount(), $message);
    }

    /**
     * Assert a notification was sent to a specific notifiable
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @param string $notificationClass Notification class name
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationSentTo(
        EntityInterface|AnonymousNotifiable $notifiable,
        string $notificationClass,
        string $message = '',
    ): void {
        $this->assertThat(
            ['notifiable' => $notifiable, 'class' => $notificationClass],
            new NotificationSentTo(),
            $message,
        );
    }

    /**
     * Assert a notification at a specific index was sent to a notifiable
     *
     * @param int $at Notification index (0-based)
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @param string $notificationClass Notification class name
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationSentToAt(
        int $at,
        EntityInterface|AnonymousNotifiable $notifiable,
        string $notificationClass,
        string $message = '',
    ): void {
        $this->assertThat(
            ['notifiable' => $notifiable, 'class' => $notificationClass],
            new NotificationSentTo($at),
            $message,
        );
    }

    /**
     * Assert a notification was not sent to a specific notifiable
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @param string $notificationClass Notification class name
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationNotSentTo(
        EntityInterface|AnonymousNotifiable $notifiable,
        string $notificationClass,
        string $message = '',
    ): void {
        $notifications = TestNotificationSender::getNotificationsFor($notifiable, $notificationClass);
        $this->assertEmpty(
            $notifications,
            $message ?: "Notification {$notificationClass} was sent to notifiable unexpectedly",
        );
    }

    /**
     * Assert a notification was sent through a specific channel
     *
     * @param string $channel Channel name
     * @param string $notificationClass Notification class name
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationSentToChannel(
        string $channel,
        string $notificationClass,
        string $message = '',
    ): void {
        $this->assertThat(
            ['channel' => $channel, 'class' => $notificationClass],
            new NotificationSentToChannel(),
            $message,
        );
    }

    /**
     * Assert an on-demand notification was sent
     *
     * @param string $notificationClass Notification class name
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertOnDemandNotificationSent(string $notificationClass, string $message = ''): void
    {
        $this->assertThat($notificationClass, new OnDemandNotificationSent(), $message);
    }

    /**
     * Assert a notification was sent a specific number of times
     *
     * @param string $notificationClass Notification class name
     * @param int $times Expected number of times
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationSentTimes(string $notificationClass, int $times, string $message = ''): void
    {
        $this->assertThat($notificationClass, new NotificationSentTimes($times), $message);
    }

    /**
     * Assert a notification was sent to a notifiable a specific number of times
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @param string $notificationClass Notification class name
     * @param int $times Expected number of times
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationSentToTimes(
        EntityInterface|AnonymousNotifiable $notifiable,
        string $notificationClass,
        int $times,
        string $message = '',
    ): void {
        $notifications = TestNotificationSender::getNotificationsFor($notifiable, $notificationClass);
        $actualCount = count($notifications);

        $this->assertEquals(
            $times,
            $actualCount,
            $message ?: "Expected {$notificationClass} to be sent to notifiable {$times} times, but was sent {$actualCount} times",
        );
    }

    /**
     * Assert a notification contains specific data
     *
     * @param string $notificationClass Notification class name
     * @param string $key Data key
     * @param mixed $value Expected value
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotificationDataContains(
        string $notificationClass,
        string $key,
        mixed $value,
        string $message = '',
    ): void {
        $this->assertThat(
            ['class' => $notificationClass, 'key' => $key, 'value' => $value],
            new NotificationDataContains(),
            $message,
        );
    }

    /**
     * Get all captured notifications
     *
     * @return array<array<string, mixed>>
     */
    public function getNotifications(): array
    {
        return TestNotificationSender::getNotifications();
    }

    /**
     * Get notifications of a specific class
     *
     * @param string $notificationClass Notification class name
     * @return array<array<string, mixed>>
     */
    public function getNotificationsByClass(string $notificationClass): array
    {
        return TestNotificationSender::getNotificationsByClass($notificationClass);
    }

    /**
     * Get notifications for a specific notifiable
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @param string $notificationClass Notification class name
     * @return array<array<string, mixed>>
     */
    public function getNotificationsFor(
        EntityInterface|AnonymousNotifiable $notifiable,
        string $notificationClass,
    ): array {
        return TestNotificationSender::getNotificationsFor($notifiable, $notificationClass);
    }

    /**
     * Get notifications sent through a specific channel
     *
     * @param string $channel Channel name
     * @return array<array<string, mixed>>
     */
    public function getNotificationsByChannel(string $channel): array
    {
        return TestNotificationSender::getNotificationsByChannel($channel);
    }

    /**
     * Get on-demand notifications (sent to AnonymousNotifiable)
     *
     * @return array<array<string, mixed>>
     */
    public function getOnDemandNotifications(): array
    {
        return TestNotificationSender::getOnDemandNotifications();
    }
}
