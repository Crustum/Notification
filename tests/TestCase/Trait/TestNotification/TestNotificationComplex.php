<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Trait\TestNotification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;
use DateTime;

/**
 * Test Notification With Complex Properties
 *
 * Tests serialization with multiple property types
 */
class TestNotificationComplex extends Notification
{
    /**
     * @var string
     */
    protected string $title;

    /**
     * @var int
     */
    protected int $entityId;

    /**
     * @var array<string>
     */
    protected array $tags;

    /**
     * @var mixed
     */
    protected mixed $post;

    /**
     * @var \DateTime
     */
    protected DateTime $createdAt;

    /**
     * Constructor
     *
     * @param string $title Title
     * @param int $entityId Entity ID
     * @param array<string> $tags Tags
     * @param mixed $post Post entity or data
     * @param \DateTime $createdAt Created at
     */
    public function __construct(string $title, int $entityId, array $tags, mixed $post, DateTime $createdAt)
    {
        $this->title = $title;
        $this->entityId = $entityId;
        $this->tags = $tags;
        $this->post = $post;
        $this->createdAt = $createdAt;
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
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get entity ID
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
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
     * Get post
     *
     * @return mixed
     */
    public function getPost(): mixed
    {
        return $this->post;
    }

    /**
     * Get created at
     *
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
