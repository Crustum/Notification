<?php
declare(strict_types=1);

namespace Crustum\Notification;

use InvalidArgumentException;

/**
 * Anonymous Notifiable
 *
 * Allows sending notifications without a persistent entity.
 * Useful for sending notifications to external channels (mail, slack, webhook, etc.)
 * without needing a database entity.
 *
 * Usage:
 * ```
 * $anonymous = new AnonymousNotifiable();
 * $anonymous
 *     ->route('mail', 'admin@example.com')
 *     ->notify(new SystemAlertNotification('Server down'));
 * ```
 */
class AnonymousNotifiable
{
    /**
     * Notification routing information for each channel
     *
     * @var array<string, mixed>
     */
    protected array $routes = [];

    /**
     * Set routing information for a channel
     *
     * @param string $channel Channel name
     * @param mixed $route Routing information (channel name, webhook URL, etc.)
     * @return $this
     * @throws \InvalidArgumentException When attempting to route database channel
     */
    public function route(string $channel, mixed $route)
    {
        if ($channel === 'database') {
            throw new InvalidArgumentException(
                __('The database channel does not support on-demand notifications.'),
            );
        }

        $this->routes[$channel] = $route;

        return $this;
    }

    /**
     * Send a notification
     *
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return void
     */
    public function notify(Notification $notification): void
    {
        NotificationManager::send($this, $notification);
    }

    /**
     * Send a notification immediately
     *
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @param array<string>|null $channels Optional specific channels to use
     * @return void
     */
    public function notifyNow(Notification $notification, ?array $channels = null): void
    {
        NotificationManager::sendNow($this, $notification, $channels);
    }

    /**
     * Get routing information for a channel
     *
     * @param string $channel Channel name
     * @param \Crustum\Notification\Notification|null $notification The notification instance
     * @return mixed The routing information
     */
    public function routeNotificationFor(string $channel, ?Notification $notification = null): mixed
    {
        return $this->routes[$channel] ?? null;
    }

    /**
     * Get the source model name
     *
     * @return string Always returns 'Anonymous'
     */
    public function getSource(): string
    {
        return 'Anonymous';
    }

    /**
     * Get a value from the anonymous notifiable
     *
     * @param string $field Field name
     * @return mixed The value
     */
    public function get(string $field): mixed
    {
        return $this->routes[$field] ?? null;
    }
}
