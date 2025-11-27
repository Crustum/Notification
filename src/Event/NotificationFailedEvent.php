<?php
declare(strict_types=1);

namespace Crustum\Notification\Event;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Crustum\Notification\Notification;
use Throwable;

/**
 * Notification Failed Event
 *
 * Dispatched when a notification fails to send through a channel.
 * Contains the notifiable entity, notification instance, channel name, and exception.
 */
class NotificationFailedEvent extends Event
{
    /**
     * Event name constant
     *
     * @var string
     */
    public const NAME = 'Model.Notification.failed';

    /**
     * Constructor
     *
     * @param \Cake\Datasource\EntityInterface $notifiable The entity that should have received the notification
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @param string $channel The channel name that failed
     * @param \Throwable $exception The exception that occurred
     */
    public function __construct(
        EntityInterface $notifiable,
        Notification $notification,
        string $channel,
        Throwable $exception,
    ) {
        parent::__construct(self::NAME, null, [
            'notifiable' => $notifiable,
            'notification' => $notification,
            'channel' => $channel,
            'exception' => $exception,
        ]);
    }

    /**
     * Get the notifiable entity
     *
     * @return \Cake\Datasource\EntityInterface
     */
    public function getNotifiable(): EntityInterface
    {
        return $this->getData('notifiable');
    }

    /**
     * Get the notification instance
     *
     * @return \Crustum\Notification\Notification
     */
    public function getNotification(): Notification
    {
        return $this->getData('notification');
    }

    /**
     * Get the channel name
     *
     * @return string
     */
    public function getChannel(): string
    {
        return $this->getData('channel');
    }

    /**
     * Get the exception
     *
     * @return \Throwable
     */
    public function getException(): Throwable
    {
        return $this->getData('exception');
    }
}
