<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Test Notification class
 */
class TestNotification extends Notification
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
}
