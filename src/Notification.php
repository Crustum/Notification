<?php
declare(strict_types=1);

namespace Crustum\Notification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\Message\DatabaseMessage;
use Crustum\Notification\Trait\SerializesNotificationTrait;

/**
 * Notification Base Class
 *
 * Abstract base class for all notification types.
 * Concrete notification classes must extend this and implement the via() method.
 *
 * Uses SerializesNotification trait for automatic queue serialization.
 *
 * Usage:
 * ```
 * class WelcomeNotification extends Notification
 * {
 *     public function via(EntityInterface|AnonymousNotifiable $notifiable): array
 *     {
 *         return ['database', 'mail'];
 *     }
 *
 *     public function toDatabase(EntityInterface|AnonymousNotifiable $notifiable): array
 *     {
 *         return ['message' => 'Welcome!'];
 *     }
 * }
 * ```
 */
abstract class Notification
{
    use SerializesNotificationTrait;

    /**
     * Unique notification identifier
     *
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * Locale for this notification
     *
     * @var string|null
     */
    protected ?string $locale = null;

    /**
     * Queue name for queued notifications
     *
     * @var string|null
     */
    protected ?string $queue = null;

    /**
     * Queue connection name
     *
     * @var string|null
     */
    protected ?string $connection = null;

    /**
     * Delay in seconds before sending queued notification
     *
     * @var int|null
     */
    protected ?int $delay = null;

    /**
     * Get the notification delivery channels
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @return array<string> Array of channel names
     */
    abstract public function via(EntityInterface|AnonymousNotifiable $notifiable): array;

    /**
     * Set the notification ID
     *
     * @param string $id Unique identifier
     * @return void
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the notification ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set the locale for this notification
     *
     * @param string $locale Locale code
     * @return $this
     */
    public function locale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get the locale for this notification
     *
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Set the queue name for queued notifications
     *
     * @param string $queue Queue name
     * @return $this
     */
    public function onQueue(string $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Get the queue name
     *
     * @return string|null
     */
    public function getQueue(): ?string
    {
        return $this->queue;
    }

    /**
     * Set the queue connection for queued notifications
     *
     * @param string $connection Connection name
     * @return $this
     */
    public function onConnection(string $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the queue connection
     *
     * @return string|null
     */
    public function getConnection(): ?string
    {
        return $this->connection;
    }

    /**
     * Set delay for queued notifications
     *
     * @param int $seconds Delay in seconds
     * @return $this
     */
    public function delay(int $seconds)
    {
        $this->delay = $seconds;

        return $this;
    }

    /**
     * Get the delay in seconds
     *
     * @return int|null
     */
    public function getDelay(): ?int
    {
        return $this->delay;
    }

    /**
     * Get the database representation of the notification
     *
     * Override this method to provide notification data for database storage.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @return \Crustum\Notification\Message\DatabaseMessage|array<string, mixed> Database message or array
     */
    public function toDatabase(EntityInterface|AnonymousNotifiable $notifiable): DatabaseMessage|array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Get the array representation of the notification
     *
     * Override this method to provide generic notification data.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @return array<string, mixed> Notification data
     */
    public function toArray(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [];
    }

    /**
     * Determine if notification should be sent
     *
     * Override this method to conditionally send notifications based on
     * notifiable entity or channel.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity to notify
     * @param string $channel The channel name
     * @return bool True to send, false to skip
     */
    public function shouldSend(EntityInterface|AnonymousNotifiable $notifiable, string $channel): bool
    {
        return true;
    }

    /**
     * Get the mail representation of the notification
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @return \Crustum\Notification\Message\MailMessage|string|null
     */
    public function toMail(EntityInterface|AnonymousNotifiable $notifiable): mixed
    {
        return null;
    }
}
