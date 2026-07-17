<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

/**
 * Backed enum channel names for NotificationManager tests
 */
enum TestChannelName: string
{
    case Database = 'database';
    case Mail = 'mail';
}
