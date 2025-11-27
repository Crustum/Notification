<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

/**
 * NotificationSentTimes
 *
 * Asserts that a notification was sent a specific number of times
 *
 * @internal
 */
class NotificationSentTimes extends NotificationConstraintBase
{
    /**
     * Expected count
     *
     * @var int
     */
    protected int $expectedCount;

    /**
     * Constructor
     *
     * @param int $expectedCount Expected number of times
     * @param int|null $at Optional index
     */
    public function __construct(int $expectedCount, ?int $at = null)
    {
        parent::__construct($at);
        $this->expectedCount = $expectedCount;
    }

    /**
     * Checks if notification was sent expected number of times
     *
     * @param mixed $other Notification class name
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $notificationClass = $other;
        $notifications = $this->getNotifications();

        $count = 0;
        foreach ($notifications as $notification) {
            if ($notification['notification_class'] === $notificationClass) {
                $count++;
            }
        }

        return $count === $this->expectedCount;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('notification was sent %d times', $this->expectedCount);
    }
}
