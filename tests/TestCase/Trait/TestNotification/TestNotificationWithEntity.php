<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Trait\TestNotification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Test Notification With Entity Property
 */
class TestNotificationWithEntity extends Notification
{
    /**
     * @var mixed
     */
    protected mixed $post;

    /**
     * Constructor
     *
     * @param mixed $post Post entity or data
     */
    public function __construct(mixed $post)
    {
        $this->post = $post;
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
     * Get post
     *
     * @return mixed
     */
    public function getPost(): mixed
    {
        return $this->post;
    }
}
