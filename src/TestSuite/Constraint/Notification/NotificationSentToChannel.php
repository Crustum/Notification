<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

/**
 * NotificationSentToChannel
 *
 * Asserts that a notification was sent through a specific channel
 *
 * @internal
 */
class NotificationSentToChannel extends NotificationConstraintBase
{
    /**
     * Checks if notification was sent through the channel
     *
     * @param mixed $other Array with 'channel' and 'class' keys
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $channel = $other['channel'];
        $notificationClass = $other['class'];

        $notifications = $this->getNotifications();

        foreach ($notifications as $notification) {
            if (
                $notification['notification_class'] === $notificationClass &&
                in_array($channel, $notification['channels'])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf('notification #%d was sent through channel', $this->at);
        }

        return 'notification was sent through channel';
    }
}
