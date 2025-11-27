<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Job;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Test notification for job testing
 */
class TestJobNotification extends Notification
{
    /**
     * @var string
     */
    protected string $title;

    /**
     * Constructor
     *
     * @param string $title Notification title
     */
    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * Get notification delivery channels
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable entity
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get notification data for database storage
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable entity
     * @return array<string, mixed>
     */
    public function toArray(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return [
            'title' => $this->title,
        ];
    }
}
