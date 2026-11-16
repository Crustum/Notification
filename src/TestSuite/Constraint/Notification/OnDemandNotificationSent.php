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
     * @param mixed $other Notification class name, or array with 'class' and optional 'data' keys
     * @return bool
     */
    protected function matches(mixed $other): bool
    {
        $notificationClass = is_array($other) ? $other['class'] : $other;
        $expectedData = is_array($other) ? ($other['data'] ?? null) : null;
        $notifications = $this->getNotifications();

        foreach ($notifications as $notification) {
            if (
                $notification['notifiable'] instanceof AnonymousNotifiable &&
                $notification['notification_class'] === $notificationClass
            ) {
                if ($expectedData === null || $this->notificationDataMatches($notification, $expectedData)) {
                    return true;
                }
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
