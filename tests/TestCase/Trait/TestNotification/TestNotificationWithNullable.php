<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Trait\TestNotification;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Test Notification With Nullable Property
 */
class TestNotificationWithNullable extends Notification
{
    /**
     * @var string|null
     */
    protected ?string $optionalValue;

    /**
     * Constructor
     *
     * @param string|null $optionalValue Optional value
     */
    public function __construct(?string $optionalValue)
    {
        $this->optionalValue = $optionalValue;
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
     * Get optional value
     *
     * @return string|null
     */
    public function getOptionalValue(): ?string
    {
        return $this->optionalValue;
    }
}
