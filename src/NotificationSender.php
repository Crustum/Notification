<?php
declare(strict_types=1);

namespace Crustum\Notification;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\I18n\I18n;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\QueueManager;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Crustum\Notification\Job\SendQueuedNotificationJob;
use Crustum\Notification\Model\Entity\Notification as NotificationEntity;
use Throwable;

/**
 * Notification Sender
 *
 * Handles the logic of dispatching notifications to entities through channels.
 * Manages locale switching, event dispatching, and queue/immediate sending logic.
 *
 * @uses \Cake\ORM\Locator\LocatorAwareTrait
 */
class NotificationSender
{
    use EventDispatcherTrait;
    use LocatorAwareTrait;

    /**
     * Locale for notifications
     *
     * @var string|null
     */
    protected ?string $locale;

    /**
     * Constructor
     *
     * @param string|null $locale Optional locale for notifications
     */
    public function __construct(?string $locale = null)
    {
        $this->locale = $locale;
    }

    /**
     * Send notification to notifiables
     *
     * If notification implements ShouldQueueInterface, it will be queued.
     * Otherwise, it will be sent immediately.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return void
     */
    public function send(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification): void
    {
        if ($notification instanceof ShouldQueueInterface) {
            $this->queueNotification($notifiables, $notification);

            return;
        }

        $this->sendNow($notifiables, $notification);
    }

    /**
     * Send notification immediately
     *
     * Sends the notification immediately through all specified channels,
     * bypassing the queue even if notification implements ShouldQueueInterface.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @param array<string>|null $channels Optional channels to use
     * @return void
     */
    public function sendNow(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification, ?array $channels = null): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            $viaChannels = $channels ?: $original->via($notifiable);

            if ($viaChannels === []) {
                continue;
            }

            $this->withLocale(
                $this->preferredLocale($notifiable, $original),
                function () use ($viaChannels, $notifiable, $original): void {
                    $notificationId = Text::uuid();
                    $actualNotificationId = $notificationId;

                    foreach ($viaChannels as $channel) {
                        $notificationClone = clone $original;
                        $response = $this->sendToNotifiable($notifiable, $actualNotificationId, $notificationClone, $channel);

                        if ($channel === 'database' && $response instanceof NotificationEntity && $response->id) {
                            $actualNotificationId = $response->id;
                        }
                    }
                },
            );
        }
    }

    /**
     * Send notification to a single notifiable via a specific channel
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param string $id Unique notification ID
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @param string $channel The channel name
     * @return mixed Channel send response, or null when sending was skipped
     * @throws \Throwable When the channel fails to send the notification
     */
    protected function sendToNotifiable(EntityInterface|AnonymousNotifiable $notifiable, string $id, Notification $notification, string $channel): mixed
    {
        if (!$notification->getId()) {
            $notification->setId($id);
        }

        if (!$this->shouldSendNotification($notifiable, $notification, $channel)) {
            return null;
        }

        try {
            $channelInstance = NotificationManager::channel($channel);
            $response = $channelInstance->send($notifiable, $notification);
        } catch (Throwable $throwable) {
            $this->dispatchEvent('Model.Notification.failed', [
                'notifiable' => $notifiable,
                'notification' => $notification,
                'channel' => $channel,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }

        if (method_exists($notification, 'afterSending')) {
            $notification->afterSending($notifiable, $channel, $response);
        }

        $this->dispatchEvent('Model.Notification.sent', [
            'notifiable' => $notifiable,
            'notification' => $notification,
            'channel' => $channel,
            'response' => $response,
        ]);

        return $response;
    }

    /**
     * Determine if notification should be sent
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @param string $channel The channel name
     * @return bool True if notification should be sent
     */
    protected function shouldSendNotification(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification, string $channel): bool
    {
        $event = $this->dispatchEvent('Model.Notification.sending', [
            'notifiable' => $notifiable,
            'notification' => $notification,
            'channel' => $channel,
        ]);

        if ($event->isStopped()) {
            return false;
        }

        return $notification->shouldSend($notifiable, $channel);
    }

    /**
     * Format notifiables into a consistent iterable format
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @return iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> Normalized iterable of notifiables
     */
    protected function formatNotifiables(EntityInterface|AnonymousNotifiable|iterable $notifiables): iterable
    {
        if (!is_iterable($notifiables)) {
            return [$notifiables];
        }

        return $notifiables;
    }

    /**
     * Get the preferred locale for the notification
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @return string|null The preferred locale
     */
    protected function preferredLocale(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): ?string
    {
        if ($notification->getLocale()) {
            return $notification->getLocale();
        }

        if ($this->locale) {
            return $this->locale;
        }

        if (method_exists($notifiable, 'preferredLocale')) {
            return $notifiable->preferredLocale();
        }

        return null;
    }

    /**
     * Queue notification instances for later processing
     *
     * Optional per-channel overrides on the notification:
     * - `viaConnections(): array<string, string>` maps channel → queue config name
     * - `viaQueues(): array<string, string>` maps channel → queue name
     *
     * Missing map entries fall back to the notification's `onConnection` / `onQueue` values.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> $notifiables The entities to notify
     * @param \Crustum\Notification\Notification $notification The notification to queue
     * @return void
     */
    protected function queueNotification(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification): void
    {
        $notifiables = $this->formatNotifiables($notifiables);
        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            if (!$notifiable instanceof EntityInterface) {
                continue;
            }

            $notificationId = Text::uuid();
            $viaChannels = $original->via($notifiable);

            if ($viaChannels === []) {
                continue;
            }

            foreach ($viaChannels as $channel) {
                $queuedNotification = clone $original;

                if (!$queuedNotification->getId()) {
                    $queuedNotification->setId($notificationId);
                }

                if ($this->locale !== null) {
                    $queuedNotification->locale($this->locale);
                }

                $table = $this->getTableLocator()->get($notifiable->getSource());
                $primaryKeyField = $table->getPrimaryKey();
                if (is_array($primaryKeyField)) {
                    $primaryKeyField = $primaryKeyField[0];
                }

                $jobData = [
                    'notifiableModel' => $notifiable->getSource(),
                    'notifiableForeignKey' => (string)$notifiable->get($primaryKeyField),
                    'notification' => serialize($queuedNotification),
                    'channels' => [$channel],
                ];

                $connection = $queuedNotification->getConnection();
                if (method_exists($queuedNotification, 'viaConnections')) {
                    $connection = $queuedNotification->viaConnections()[$channel] ?? $connection;
                }

                $queue = $queuedNotification->getQueue();
                if (method_exists($queuedNotification, 'viaQueues')) {
                    $queue = $queuedNotification->viaQueues()[$channel] ?? $queue;
                }

                $options = [];
                if ($queue !== null) {
                    $options['queue'] = $queue;
                }

                if ($connection) {
                    $options['config'] = $connection;
                }

                if ($queuedNotification->getDelay()) {
                    $options['delay'] = $queuedNotification->getDelay();
                }

                QueueManager::push(
                    SendQueuedNotificationJob::class,
                    $jobData,
                    $options,
                );
            }
        }
    }

    /**
     * Execute callback with specific locale
     *
     * @param string|null $locale The locale to use
     * @param callable $callback The callback to execute
     * @return void
     */
    protected function withLocale(?string $locale, callable $callback): void
    {
        if ($locale === null) {
            $callback();

            return;
        }

        $original = I18n::getLocale();
        I18n::setLocale($locale);

        try {
            $callback();
        } finally {
            I18n::setLocale($original);
        }
    }

    /**
     * Get the routing information for a given notification channel
     *
     * Checks for a channel-specific routing method on the notifiable entity.
     * For example, for the 'telegram' channel, it looks for a method named
     * 'routeNotificationForTelegram()' on the entity.
     *
     * This allows entities to define custom routing logic for different channels:
     * - telegram → routeNotificationForTelegram()
     * - slack → routeNotificationForSlack()
     * - twilio → routeNotificationForTwilio()
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param string $channel The channel name
     * @return mixed The routing information, or null if not available
     */
    public function getRoutingInfo(EntityInterface|AnonymousNotifiable $notifiable, string $channel): mixed
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return $notifiable->routeNotificationFor($channel);
        }

        $method = 'routeNotificationFor' . Inflector::camelize($channel);

        if (method_exists($notifiable, $method)) {
            return $notifiable->{$method}();
        }

        return null;
    }
}
