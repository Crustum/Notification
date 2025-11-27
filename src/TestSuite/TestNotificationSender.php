<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;
use Crustum\Notification\NotificationManager;
use Crustum\Notification\NotificationSender;
use Crustum\Notification\ShouldQueueInterface;

/**
 * Test Notification Sender
 *
 * Captures notifications instead of sending them for testing purposes.
 * Similar to TestEmailTransport for email testing.
 *
 * Usage:
 * ```
 * // In test setup
 * TestNotificationSender::replaceAllSenders();
 *
 * // Send notifications as normal
 * $user->notify(new InvoicePaid($invoice));
 *
 * // Make assertions
 * $notifications = TestNotificationSender::getNotifications();
 * ```
 */
class TestNotificationSender extends NotificationSender
{
    /**
     * Captured notifications
     *
     * @var array<array<string, mixed>>
     */
    protected static array $notifications = [];

    /**
     * Send notification by capturing it instead of actually sending
     *
     * Overrides parent to store notification data instead of sending.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return void
     */
    public function send(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        foreach ($notifiables as $notifiable) {
            $viaChannels = $notification->via($notifiable);

            if (empty($viaChannels)) {
                continue;
            }

            static::$notifications[] = [
                'notifiable' => $notifiable,
                'notifiable_class' => get_class($notifiable),
                'notifiable_id' => static::getNotifiableKey($notifiable),
                'notification' => clone $notification,
                'notification_class' => get_class($notification),
                'channels' => $viaChannels,
                'locale' => $this->locale,
                'timestamp' => time(),
                'queued' => $notification instanceof ShouldQueueInterface,
            ];
        }
    }

    /**
     * Send notification immediately by capturing it
     *
     * For testing purposes, this behaves the same as send().
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @param array<string>|null $channels Optional array of specific channels to use
     * @return void
     */
    public function sendNow(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification, ?array $channels = null): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        foreach ($notifiables as $notifiable) {
            $viaChannels = $channels ?: $notification->via($notifiable);

            if (empty($viaChannels)) {
                continue;
            }

            static::$notifications[] = [
                'notifiable' => $notifiable,
                'notifiable_class' => get_class($notifiable),
                'notifiable_id' => static::getNotifiableKey($notifiable),
                'notification' => clone $notification,
                'notification_class' => get_class($notification),
                'channels' => $viaChannels,
                'locale' => $this->locale,
                'timestamp' => time(),
                'queued' => false,
            ];
        }
    }

    /**
     * Replace notification sender with test sender
     *
     * Similar to TestEmailTransport::replaceAllTransports()
     *
     * @return void
     */
    public static function replaceAllSenders(): void
    {
        NotificationManager::configureSender(static::class);
    }

    /**
     * Get all captured notifications
     *
     * @return array<array<string, mixed>>
     */
    public static function getNotifications(): array
    {
        return static::$notifications;
    }

    /**
     * Clear all captured notifications
     *
     * @return void
     */
    public static function clearNotifications(): void
    {
        static::$notifications = [];
    }

    /**
     * Get unique key for a notifiable entity
     *
     * For EntityInterface: Returns primary key value (e.g., "123")
     * For AnonymousNotifiable: Returns object hash with prefix
     * For other objects: Returns object hash
     *
     * @param object $notifiable The notifiable entity
     * @return string Unique identifier string
     */
    protected static function getNotifiableKey(object $notifiable): string
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return 'anonymous_' . spl_object_hash($notifiable);
        }

        if ($notifiable instanceof EntityInterface) {
            $source = $notifiable->getSource();
            if ($source) {
                $table = TableRegistry::getTableLocator()->get($source);
                $primaryKey = $table->getPrimaryKey();

                if (is_array($primaryKey)) {
                    $primaryKey = $primaryKey[0];
                }

                return (string)$notifiable->get($primaryKey);
            }
        }

        return spl_object_hash($notifiable);
    }

    /**
     * Get notifications for a specific notifiable and notification class
     *
     * @param object $notifiable The notifiable entity
     * @param string $notificationClass Notification class name
     * @return array<array<string, mixed>>
     */
    public static function getNotificationsFor(object $notifiable, string $notificationClass): array
    {
        $notifiableClass = get_class($notifiable);
        $notifiableId = static::getNotifiableKey($notifiable);

        return array_filter(
            static::$notifications,
            fn($n) => $n['notifiable_class'] === $notifiableClass &&
                $n['notifiable_id'] === $notifiableId &&
                $n['notification_class'] === $notificationClass,
        );
    }

    /**
     * Get notifications sent through a specific channel
     *
     * @param string $channel Channel name
     * @return array<array<string, mixed>>
     */
    public static function getNotificationsByChannel(string $channel): array
    {
        return array_filter(
            static::$notifications,
            fn($n) => in_array($channel, $n['channels']),
        );
    }

    /**
     * Get notifications of a specific class
     *
     * @param string $notificationClass Notification class name
     * @return array<array<string, mixed>>
     */
    public static function getNotificationsByClass(string $notificationClass): array
    {
        return array_filter(
            static::$notifications,
            fn($n) => $n['notification_class'] === $notificationClass,
        );
    }

    /**
     * Get on-demand notifications (sent to AnonymousNotifiable)
     *
     * @return array<array<string, mixed>>
     */
    public static function getOnDemandNotifications(): array
    {
        return array_filter(
            static::$notifications,
            fn($n) => $n['notifiable'] instanceof AnonymousNotifiable,
        );
    }
}
