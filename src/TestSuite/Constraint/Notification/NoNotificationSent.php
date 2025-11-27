<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

/**
 * NoNotificationSent
 *
 * Asserts that no notifications were sent
 *
 * @internal
 */
class NoNotificationSent extends NotificationConstraintBase
{
    /**
     * Checks if no notifications were sent
     *
     * @param mixed $other Not used
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $notifications = $this->getNotifications();

        return empty($notifications);
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        $notifications = $this->getNotifications();

        if (!empty($notifications)) {
            $classes = array_unique(array_column($notifications, 'notification_class'));

            return sprintf(
                'no notifications were sent (but found: %s)',
                implode(', ', $classes),
            );
        }

        return 'no notifications were sent';
    }
}
