<?php
declare(strict_types=1);

namespace Crustum\Notification\Event;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Crustum\Notification\Notification;

/**
 * Notification Sent Event
 *
 * Dispatched after a notification has been successfully sent through a channel.
 * Contains the notifiable entity, notification instance, channel name, and channel response.
 */
class NotificationSentEvent extends Event
{
    /**
     * Event name constant
     *
     * @var string
     */
    public const NAME = 'Model.Notification.sent';

    /**
     * Constructor
     *
     * @param \Cake\Datasource\EntityInterface $notifiable The entity that received the notification
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @param string $channel The channel name used to send
     * @param mixed $response The channel's response
     */
    public function __construct(
        EntityInterface $notifiable,
        Notification $notification,
        string $channel,
        mixed $response = null,
    ) {
        parent::__construct(self::NAME, null, [
            'notifiable' => $notifiable,
            'notification' => $notification,
            'channel' => $channel,
            'response' => $response,
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
     * Get the channel response
     *
     * @return mixed
     */
    public function getResponse(): mixed
    {
        return $this->getData('response');
    }
}
