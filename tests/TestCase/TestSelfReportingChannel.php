<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\Event\Event;
use Crustum\Notification\Channel\ChannelInterface;
use Crustum\Notification\Notification;
use Crustum\Notification\NotificationSender;
use RuntimeException;

/**
 * Channel that dispatches the failed event itself and then throws
 *
 * Mirrors the upstream push-channel fixture: reports failure through the
 * event system and marks the sender flag so the sender does not dispatch
 * the failed event a second time.
 */
class TestSelfReportingChannel implements ChannelInterface
{
    /**
     * @param array<string, mixed> $config Channel configuration with `sender` and `channelName` keys
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Channel configuration
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * @inheritDoc
     */
    public function send(object $notifiable, Notification $notification): mixed
    {
        /** @var \Crustum\Notification\NotificationSender|null $sender */
        $sender = $this->config['sender'] ?? null;
        $channel = $this->config['channelName'] ?? 'push';

        if ($sender instanceof NotificationSender) {
            $sender->getEventManager()->dispatch(new Event('Model.Notification.failed', null, [
                'notifiable' => $notifiable,
                'notification' => $notification,
                'channel' => $channel,
                'exception' => new RuntimeException('Push delivery failed'),
            ]));
            $sender->markFailedEventDispatched();
        }

        throw new RuntimeException('Push delivery failed');
    }
}
