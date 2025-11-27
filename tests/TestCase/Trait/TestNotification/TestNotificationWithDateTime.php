<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Trait\TestNotification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;
use DateTime;

/**
 * Test Notification With DateTime Property
 */
class TestNotificationWithDateTime extends Notification
{
    /**
     * @var \DateTime
     */
    protected DateTime $scheduledAt;

    /**
     * Constructor
     *
     * @param \DateTime $scheduledAt Scheduled at
     */
    public function __construct(DateTime $scheduledAt)
    {
        $this->scheduledAt = $scheduledAt;
    }

    /**
     * Get channels
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get scheduled at
     *
     * @return \DateTime
     */
    public function getScheduledAt(): DateTime
    {
        return $this->scheduledAt;
    }
}
