<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Job;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;
use DateTime;

/**
 * Complex test notification for job testing
 */
class TestComplexJobNotification extends Notification
{
    /**
     * @var string
     */
    protected string $title;

    /**
     * @var array<string>
     */
    protected array $tags;

    /**
     * @var \DateTime
     */
    protected DateTime $createdAt;

    /**
     * Constructor
     *
     * @param string $title Notification title
     * @param array<string> $tags Tags
     * @param \DateTime $createdAt Created date
     */
    public function __construct(string $title, array $tags, DateTime $createdAt)
    {
        $this->title = $title;
        $this->tags = $tags;
        $this->createdAt = $createdAt;
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
     * Get tags
     *
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Get created date
     *
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
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
            'tags' => $this->tags,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
