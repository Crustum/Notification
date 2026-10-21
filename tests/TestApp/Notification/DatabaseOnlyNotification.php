<?php
declare(strict_types=1);

namespace TestApp\Notification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Message\DatabaseMessage;
use Crustum\Notification\Notification;

/**
 * Notification with payload only in the database message.
 */
class DatabaseOnlyNotification extends Notification
{
    /**
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable.
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database'];
    }

    /**
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable.
     * @return array<string, mixed>
     */
    public function toArray(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [];
    }

    /**
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable.
     * @return \Crustum\Notification\Message\DatabaseMessage
     */
    public function toDatabase(EntityInterface|AnonymousNotifiable $notifiable): DatabaseMessage
    {
        return (new DatabaseMessage())->data(['post_id' => 3]);
    }
}
