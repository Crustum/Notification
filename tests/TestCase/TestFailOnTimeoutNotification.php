<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\ShouldQueueInterface;

/**
 * Test Fail On Timeout Notification
 *
 * Queued notification that should fail on timeout
 */
class TestFailOnTimeoutNotification extends TestDatabaseNotification implements ShouldQueueInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->failOnTimeout();
    }

    /**
     * @inheritDoc
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['database'];
    }
}
