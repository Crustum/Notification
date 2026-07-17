<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;

/**
 * Notification with afterSending hook
 */
class TestNotificationWithAfterSending extends TestDatabaseNotification
{
    /**
     * @var \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|null
     */
    public static mixed $afterSendingNotifiable = null;

    /**
     * @var string|null
     */
    public static ?string $afterSendingChannel = null;

    /**
     * @var mixed
     */
    public static mixed $afterSendingResponse = null;

    /**
     * Reset static capture state
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$afterSendingNotifiable = null;
        static::$afterSendingChannel = null;
        static::$afterSendingResponse = null;
    }

    /**
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable Notifiable
     * @param string $channel Channel name
     * @param mixed $response Channel response
     * @return void
     */
    public function afterSending(EntityInterface|AnonymousNotifiable $notifiable, string $channel, mixed $response): void
    {
        static::$afterSendingNotifiable = $notifiable;
        static::$afterSendingChannel = $channel;
        static::$afterSendingResponse = $response;
    }
}
