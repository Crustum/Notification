<?php
declare(strict_types=1);

namespace Crustum\Notification\Event;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Crustum\Notification\Notification;

/**
 * Notification Sending Event
 *
 * Dispatched before a notification is sent through a channel.
 * Can be stopped to prevent notification from being sent.
 */
class NotificationSendingEvent extends Event
{
    /**
     * Event name constant
     *
     * @var string
     */
    public const NAME = 'Model.Notification.sending';

    /**
     * Constructor
     *
     * @param \Cake\Datasource\EntityInterface $notifiable The entity to receive the notification
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @param string $channel The channel name to send through
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
