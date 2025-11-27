<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

use Crustum\Notification\TestSuite\TestNotificationSender;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Base class for all notification assertion constraints
 *
 * @internal
 */
abstract class NotificationConstraintBase extends Constraint
{
    /**
     * Notification index to check
     *
     * @var int|null
     */
    protected ?int $at = null;

    /**
     * Constructor
     *
     * @param int|null $at Optional index of specific notification to check
     */
    public function __construct(?int $at = null)
    {
        $this->at = $at;
    }

    /**
     * Get the notification or notifications to check
     *
     * @return array<array<string, mixed>>
     */
    protected function getNotifications(): array
    {
        $notifications = TestNotificationSender::getNotifications();

        if ($this->at !== null) {
            if (!isset($notifications[$this->at])) {
                return [];
            }

            return [$notifications[$this->at]];
        }

        return $notifications;
    }
}
