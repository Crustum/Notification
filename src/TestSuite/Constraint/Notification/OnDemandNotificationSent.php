<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

use Crustum\Notification\AnonymousNotifiable;

/**
 * OnDemandNotificationSent
 *
 * Asserts that an on-demand notification (to AnonymousNotifiable) was sent
 *
 * @internal
 */
class OnDemandNotificationSent extends NotificationConstraintBase
{
    /**
     * Checks if on-demand notification was sent
     *
     * @param mixed $other Notification class name
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $notificationClass = $other;
        $notifications = $this->getNotifications();

        foreach ($notifications as $notification) {
            if (
                $notification['notifiable'] instanceof AnonymousNotifiable &&
                $notification['notification_class'] === $notificationClass
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
        return 'on-demand notification was sent';
    }
}
