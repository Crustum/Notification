<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Crustum\Notification\Notification;
use Crustum\Notification\ShouldQueueInterface;

/**
 * Queued notification with partial viaQueues map
 */
class TestQueuedNotificationWithViaQueues extends Notification implements ShouldQueueInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->onConnection('redis');
        $this->onQueue('dummy');
    }

    /**
     * @inheritDoc
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'admin_notifications',
        ];
    }

    /**
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable): array
    {
        return ['message' => 'queued'];
    }
}
