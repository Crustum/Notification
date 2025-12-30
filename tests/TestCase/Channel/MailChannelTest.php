<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Channel;

use Cake\Mailer\TransportFactory;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Channel\MailChannel;

/**
 * MailChannel Test Case
 *
 * Tests the mail notification channel
 */
class MailChannelTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Crustum\Notification\Channel\MailChannel
     */
    protected MailChannel $channel;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        TransportFactory::drop('test');
        TransportFactory::setConfig('test', [
            'className' => 'Debug',
        ]);

        $this->channel = new MailChannel([
            'profile' => [
                'transport' => 'test',
                'from' => ['noreply@example.com' => 'Test App'],
            ],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        TransportFactory::drop('test');

        parent::tearDown();
    }

    /**
     * Test send with MailMessage
     *
     * @return void
     */
    public function testSendWithMailMessage(): void
    {
        $notifiable = new Entity([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);
        $notifiable->setSource('Users');

        $notification = new TestMailNotification();

        $result = $this->channel->send($notifiable, $notification);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test send with string message
     *
     * @return void
     */
    public function testSendWithStringMessage(): void
    {
        $notifiable = new Entity([
            'email' => 'user@example.com',
        ]);
        $notifiable->setSource('Users');

        $notification = new TestSimpleMailNotification();

        $result = $this->channel->send($notifiable, $notification);

        $this->assertIsArray($result);
    }

    /**
     * Test send with routeNotificationForMail
     *
     * @return void
     */
    public function testSendWithRouting(): void
    {
        $notifiable = new TestRoutableEntity();
        $notification = new TestMailNotification();

        $result = $this->channel->send($notifiable, $notification);

        $this->assertIsArray($result);
    }

    /**
     * Test send returns null when no email
     *
     * @return void
     */
    public function testSendReturnsNullWhenNoEmail(): void
    {
        $notifiable = new Entity(['id' => 1]);
        $notifiable->setSource('Users');

        $notification = new TestMailNotification();

        $result = $this->channel->send($notifiable, $notification);

        $this->assertNull($result);
    }

    /**
     * Test send returns null when toMail returns null
     *
     * @return void
     */
    public function testSendReturnsNullWhenToMailReturnsNull(): void
    {
        $notifiable = new Entity(['email' => 'user@example.com']);
        $notifiable->setSource('Users');

        $notification = new TestNullMailNotification();

        $result = $this->channel->send($notifiable, $notification);

        $this->assertNull($result);
    }

    /**
     * Test configureMailer with full MailMessage
     *
     * @return void
     */
    public function testConfigureMailerWithFullMessage(): void
    {
        $notifiable = new Entity([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);
        $notifiable->setSource('Users');

        $notification = new TestComplexMailNotification();

        $result = $this->channel->send($notifiable, $notification);

        $this->assertIsArray($result);
    }
}
