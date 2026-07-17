<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Crustum\Notification\ShouldQueueInterface;

/**
 * Queued notification for single-entity format coverage
 */
class TestQueuedDatabaseNotification extends TestDatabaseNotification implements ShouldQueueInterface
{
}
