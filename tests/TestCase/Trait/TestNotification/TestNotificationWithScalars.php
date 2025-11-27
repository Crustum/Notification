<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Trait\TestNotification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Test Notification With Scalar Properties
 */
class TestNotificationWithScalars extends Notification
{
    /**
     * @var string
     */
    protected string $title;

    /**
     * @var int
     */
    protected int $count;

    /**
     * @var bool
     */
    protected bool $active;

    /**
     * Constructor
     *
     * @param string $title Title
     * @param int $count Count
     * @param bool $active Active flag
     */
    public function __construct(string $title, int $count, bool $active)
    {
        $this->title = $title;
        $this->count = $count;
        $this->active = $active;
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
     * Get count
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Check if active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }
}
