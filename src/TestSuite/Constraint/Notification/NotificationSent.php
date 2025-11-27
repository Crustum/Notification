<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

/**
 * NotificationSent
 *
 * Asserts that a notification of a specific class was sent
 *
 * @internal
 */
class NotificationSent extends NotificationConstraintBase
{
    /**
     * Checks if notification was sent
     *
     * @param mixed $other Notification class name
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $notificationClass = $other;
        $notifications = $this->getNotifications();

        foreach ($notifications as $notification) {
            if ($notification['notification_class'] === $notificationClass) {
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
            return sprintf('notification #%d was sent', $this->at);
        }

        return 'notification was sent';
    }
}
