<?php
declare(strict_types=1);

namespace Crustum\Notification\Event;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Crustum\Notification\Notification;

/**
 * Notification Skipped Event
 *
 * Dispatched when a notification is skipped (not sent) because the notification's
 * shouldSend() method returned false or the sending event was stopped.
 */
class NotificationSkippedEvent extends Event
{
    /**
     * Event name constant
     *
     * @var string
     */
    public const NAME = 'Model.Notification.skipped';

    /**
     * Constructor
     *
     * @param \Cake\Datasource\EntityInterface $notifiable The entity that would have received the notification
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @param string $channel The channel name that was skipped
     */
    public function __construct(
        EntityInterface $notifiable,
        Notification $notification,
        string $channel,
    ) {
        parent::__construct(self::NAME, null, [
            'notifiable' => $notifiable,
            'notification' => $notification,
            'channel' => $channel,
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
}
