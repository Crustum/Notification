<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Channel;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Message\MailMessage;
use Crustum\Notification\Notification;

/**
 * Test mail notification
 */
class TestMailNotification extends Notification
{
    /**
     * Get notification delivery channels
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable entity
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get mail representation
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable entity
     * @return \Crustum\Notification\Message\MailMessage
     */
    public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
    {
        return MailMessage::create()
            ->subject('Test Notification')
            ->greeting('Hello!')
            ->line('This is a test notification.')
            ->action('View', 'https://example.com')
            ->salutation('Thanks!');
    }

    /**
     * Get array representation
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable entity
     * @return array<string, mixed>
     */
    public function toArray(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['message' => 'Test'];
    }
}
