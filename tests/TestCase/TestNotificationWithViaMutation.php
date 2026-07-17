<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

/**
 * Notification that mutates state inside via()
 */
class TestNotificationWithViaMutation extends TestDatabaseNotification
{
    /**
     * @var string|null
     */
    public ?string $channelData = null;

    /**
     * @inheritDoc
     */
    public function via($notifiable): array
    {
        $this->channelData = 'mutated-in-via';

        return ['database'];
    }
}
