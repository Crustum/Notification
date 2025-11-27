<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Channel;

use Cake\ORM\Entity;
use Crustum\Notification\Notification;

/**
 * Test entity with routeNotificationForMail method
 */
class TestRoutableEntity extends Entity
{
    /**
     * Route notification for mail channel
     *
     * @param \Crustum\Notification\Notification $notification Notification instance
     * @return string
     */
    public function routeNotificationForMail(Notification $notification): string
    {
        return 'routed@example.com';
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource(): string
    {
        return 'Users';
    }
}
