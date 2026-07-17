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
     * Test channel works with backed enums
     *
     * @return void
     */
    public function testChannelWorksWithBackedEnum(): void
    {
        $channel = NotificationManager::channel(TestChannelName::Database);

        $this->assertInstanceOf(DatabaseChannel::class, $channel);
    }

    /**
     * Test channel works with unit enums
     *
     * @return void
     */
    public function testChannelWorksWithUnitEnum(): void
    {
        $channel = NotificationManager::channel(TestUnitChannelName::mail);

        $this->assertInstanceOf(MailChannel::class, $channel);
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

    /**
     * Test getSender reuses a single instance
     *
     * @return void
     */
    public function testGetSenderReusesInstance(): void
    {
        NotificationManager::resetSender();

        $first = NotificationManager::getSender();
        $second = NotificationManager::getSender();

        $this->assertSame($first, $second);
    }

    /**
     * Test getSender rebuilds when locale changes
     *
     * @return void
     */
    public function testGetSenderRebuildsWhenLocaleChanges(): void
    {
        NotificationManager::resetSender();

        $first = NotificationManager::getSender('en_US');
        $second = NotificationManager::getSender('fr_FR');

        $this->assertNotSame($first, $second);
    }

    /**
     * Tear down static manager state
     *
     * @return void
     */
    protected function tearDown(): void
    {
        NotificationManager::resetSender();

        parent::tearDown();
    }
}
