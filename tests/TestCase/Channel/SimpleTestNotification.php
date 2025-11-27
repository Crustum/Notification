<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Channel;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Simple Test Notification
 *
 * Minimal notification for testing database channel
 */
class SimpleTestNotification extends Notification
{
    /**
     * Get channels
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get database data
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @return array<string, mixed>
     */
    public function toDatabase(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [
            'test' => 'data',
        ];
    }
}
