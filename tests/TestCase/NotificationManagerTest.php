<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\TestSuite\TestCase;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Channel\DatabaseChannel;
use Crustum\Notification\Channel\MailChannel;
use Crustum\Notification\NotificationManager;

/**
 * NotificationManager Test Case
 */
class NotificationManagerTest extends TestCase
{
    /**
     * Test route creates AnonymousNotifiable
     *
     * @return void
     */
    public function testRouteCreatesAnonymousNotifiable(): void
    {
        $anonymous = NotificationManager::route('mail', 'test@example.com');

        $this->assertInstanceOf(AnonymousNotifiable::class, $anonymous);
        $this->assertEquals('test@example.com', $anonymous->routeNotificationFor('mail'));
    }

    /**
     * Test routes creates AnonymousNotifiable with multiple channels
     *
     * @return void
     */
    public function testRoutesCreatesAnonymousNotifiableWithMultipleChannels(): void
    {
        $anonymous = NotificationManager::routes([
            'mail' => 'test@example.com',
            'slack' => '#slack-channel',
        ]);

        $this->assertInstanceOf(AnonymousNotifiable::class, $anonymous);
        $this->assertEquals('test@example.com', $anonymous->routeNotificationFor('mail'));
        $this->assertEquals('#slack-channel', $anonymous->routeNotificationFor('slack'));
    }

    /**
     * Test channel works with class names
     *
     * @return void
     */
    public function testChannelWorksWithClassName(): void
    {
        $channel = NotificationManager::channel(DatabaseChannel::class);

        $this->assertInstanceOf(DatabaseChannel::class, $channel);
    }

    /**
     * Test channel works with string names
     *
     * @return void
     */
    public function testChannelWorksWithStringName(): void
    {
        $channel = NotificationManager::channel('database');

        $this->assertInstanceOf(DatabaseChannel::class, $channel);
    }

    /**
     * Test configured returns list of channels
     *
     * @return void
     */
    public function testConfiguredReturnsChannels(): void
    {
        NotificationManager::setConfig('database', ['className' => DatabaseChannel::class]);
        NotificationManager::setConfig('mail', ['className' => MailChannel::class]);

        $configured = NotificationManager::configured();

        $this->assertContains('database', $configured);
        $this->assertContains('mail', $configured);
    }
}
