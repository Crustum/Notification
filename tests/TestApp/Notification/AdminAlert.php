<?php
declare(strict_types=1);

namespace TestApp\Notification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Message\DatabaseMessage;
use Crustum\Notification\Notification;

/**
 * AdminAlert Notification
 *
 * Alert notification for administrators (database only)
 */
class AdminAlert extends Notification
{
    /**
     * Alert message
     *
     * @var string
     */
    protected string $alertMessage;

    /**
     * Alert level
     *
     * @var string
     */
    protected string $level;

    /**
     * Constructor
     *
     * @param string $alertMessage Alert message
     * @param string $level Alert level
     */
    public function __construct(string $alertMessage, string $level = 'info')
    {
        $this->alertMessage = $alertMessage;
        $this->level = $level;
    }

    /**
     * Get notification channels
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Entity
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get database representation
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Entity
     * @return \Crustum\Notification\Message\DatabaseMessage
     */
    public function toDatabase(EntityInterface|AnonymousNotifiable $notifiable): DatabaseMessage
    {
        return (new DatabaseMessage())->data([
            'message' => $this->alertMessage,
            'level' => $this->level,
        ]);
    }

    /**
     * Get array representation
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Entity
     * @return array<string, mixed>
     */
    public function toArray(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [
            'message' => $this->alertMessage,
            'level' => $this->level,
        ];
    }
}
