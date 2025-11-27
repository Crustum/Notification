<?php
declare(strict_types=1);

namespace TestApp\Notification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Message\DatabaseMessage;
use Crustum\Notification\Message\MailMessage;
use Crustum\Notification\Notification;

/**
 * PostPublished Notification
 *
 * Sent when a post is published
 */
class PostPublished extends Notification
{
    /**
     * Post ID
     *
     * @var int
     */
    protected int $postId;

    /**
     * Post title
     *
     * @var string
     */
    protected string $postTitle;

    /**
     * Constructor
     *
     * @param int $postId Post ID
     * @param string $postTitle Post title
     */
    public function __construct(int $postId, string $postTitle)
    {
        $this->postId = $postId;
        $this->postTitle = $postTitle;
    }

    /**
     * Get notification channels
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Entity
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database', 'mail'];
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
            'post_id' => $this->postId,
            'post_title' => $this->postTitle,
            'message' => "Your post '{$this->postTitle}' has been published",
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
            'post_id' => $this->postId,
            'post_title' => $this->postTitle,
            'message' => "Your post '{$this->postTitle}' has been published",
        ];
    }

    /**
     * Get mail representation
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Entity
     * @return \Crustum\Notification\Message\MailMessage
     */
    public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
    {
        return MailMessage::create()
            ->subject('Your Post Has Been Published')
            ->greeting('Hello!')
            ->line("Your post '{$this->postTitle}' has been published.")
            ->action('View Post', '/posts/' . $this->postId)
            ->salutation('Best Regards');
    }
}
