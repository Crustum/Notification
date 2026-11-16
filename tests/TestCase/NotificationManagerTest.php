<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\TestSuite\TestCase;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Channel\DatabaseChannel;
use Crustum\Notification\Channel\MailChannel;
use Crustum\Notification\NotificationManager;
use Crustum\Notification\NotificationSender;
use Crustum\Notification\TestSuite\TestNotificationSender;

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
        NotificationManager::drop('database');
        NotificationManager::drop('mail');
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
     * Test sender locale setter is fluent
     *
     * @return void
     */
    public function testSenderLocaleSetterIsFluent(): void
    {
        $sender = new NotificationSender();

        $this->assertSame($sender, $sender->locale('fr'));
    }

    /**
     * Test mail is sent with manager selected locale after another notification was sent
     *
     * Ports testMailIsSentWithFacadeSelectedLocaleAfterAnotherNotificationWasSent:
     * a plain send must not swallow a later manager locale.
     *
     * @return void
     */
    public function testMailIsSentWithManagerSelectedLocaleAfterAnotherNotificationWasSent(): void
    {
        TestNotificationSender::replaceAllSenders();
        TestNotificationSender::clearNotifications();

        $anonymous = NotificationManager::route('mail', 'test@example.com');

        NotificationManager::sendNow($anonymous, new TestNotification());
        NotificationManager::locale('fr');
        NotificationManager::sendNow($anonymous, new TestNotification());

        $notifications = TestNotificationSender::getNotifications();

        $this->assertCount(2, $notifications);
        $this->assertNull($notifications[0]['locale']);
        $this->assertSame('fr', $notifications[1]['locale']);

        TestNotificationSender::clearNotifications();
    }

    /**
     * Test manager selected locale is not used for later notifications
     *
     * Ports testFacadeSelectedLocaleIsNotUsedForLaterNotifications:
     * the pending locale is consumed by a single send.
     *
     * @return void
     */
    public function testManagerSelectedLocaleIsNotUsedForLaterNotifications(): void
    {
        TestNotificationSender::replaceAllSenders();
        TestNotificationSender::clearNotifications();

        $anonymous = NotificationManager::route('mail', 'test@example.com');

        NotificationManager::locale('fr');
        NotificationManager::sendNow($anonymous, new TestNotification());
        NotificationManager::sendNow($anonymous, new TestNotification());

        $notifications = TestNotificationSender::getNotifications();

        $this->assertCount(2, $notifications);
        $this->assertSame('fr', $notifications[0]['locale']);
        $this->assertNull($notifications[1]['locale']);

        TestNotificationSender::clearNotifications();
    }

    /**
     * Tear down static manager state
     *
     * @return void
     */
    protected function tearDown(): void
    {
        TestNotificationSender::clearNotifications();
        NotificationManager::resetSender();

        parent::tearDown();
    }
}
