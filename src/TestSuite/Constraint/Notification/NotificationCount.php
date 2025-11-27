<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

/**
 * NotificationCount
 *
 * Asserts a specific count of notifications were sent
 *
 * @internal
 */
class NotificationCount extends NotificationConstraintBase
{
    /**
     * Checks if notification count matches
     *
     * @param mixed $other Expected count
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $expectedCount = $other;
        $notifications = $this->getNotifications();

        return count($notifications) === $expectedCount;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        $notifications = $this->getNotifications();
        $actualCount = count($notifications);

        return sprintf('notification count is (actual: %d)', $actualCount);
    }
}
