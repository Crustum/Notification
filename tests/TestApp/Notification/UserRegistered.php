<?php
declare(strict_types=1);

namespace TestApp\Notification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Message\DatabaseMessage;
use Crustum\Notification\Message\MailMessage;
use Crustum\Notification\Notification;

/**
 * UserRegistered Notification
 *
 * Sent when a new user registers
 */
class UserRegistered extends Notification
{
    /**
     * Username
     *
     * @var string
     */
    protected string $username;

    /**
     * Constructor
     *
     * @param string $username Username
     */
    public function __construct(string $username)
    {
        $this->username = $username;
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
            'username' => $this->username,
            'message' => "Welcome {$this->username}!",
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
            'username' => $this->username,
            'message' => "Welcome {$this->username}!",
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
            ->subject('Welcome to Our Application')
            ->greeting("Hello {$this->username}!")
            ->line('Thank you for registering with us.')
            ->line('We are excited to have you on board.')
            ->salutation('Best Regards');
    }
}
