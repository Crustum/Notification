<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

/**
 * NotificationDataContains
 *
 * Asserts that a notification contains specific data
 *
 * @internal
 */
class NotificationDataContains extends NotificationConstraintBase
{
    /**
     * Checks if notification data contains key and value
     *
     * @param mixed $other Array with 'class', 'key', and 'value' keys
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $notificationClass = $other['class'];
        $dataKey = $other['key'];
        $expectedValue = $other['value'];

        $notifications = $this->getNotifications();

        foreach ($notifications as $notificationData) {
            if ($notificationData['notification_class'] !== $notificationClass) {
                continue;
            }

            $notification = $notificationData['notification'];

            if (is_object($notification) && method_exists($notification, 'toArray')) {
                $data = $notification->toArray($notificationData['notifiable']);

                if (isset($data[$dataKey]) && $data[$dataKey] === $expectedValue) {
                    return true;
                }
            }

            if (is_object($notification) && property_exists($notification, $dataKey)) {
                if ($notification->{$dataKey} === $expectedValue) {
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
        return 'notification contains expected data';
    }
}
