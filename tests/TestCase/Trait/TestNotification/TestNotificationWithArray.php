<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Trait\TestNotification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Test Notification With Array Property
 */
class TestNotificationWithArray extends Notification
{
    /**
     * @var array<string, mixed>
     */
    protected array $items;

    /**
     * Constructor
     *
     * @param array<string, mixed> $items Items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
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
     * Get items
     *
     * @return array<string, mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
