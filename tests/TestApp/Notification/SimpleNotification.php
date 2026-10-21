<?php
declare(strict_types=1);

namespace TestApp\Notification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Simple notification with a fixed payload for panel tests.
 */
class SimpleNotification extends Notification
{
    /**
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable.
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable.
     * @return array<string, mixed>
     */
    public function toArray(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['post_id' => 9];
    }
}
