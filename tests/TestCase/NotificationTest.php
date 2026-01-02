<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Crustum\Notification\AnonymousNotifiable;

/**
 * Notification Test Case
 *
 * Tests the base Notification class methods
 */
class NotificationTest extends TestCase
{
    /**
     * Test locale method sets and returns locale
     *
     * @return void
     */
    public function testLocaleMethodSetsAndReturnsLocale(): void
    {
        $notification = new TestNotification();
        $result = $notification->locale('en_US');

        $this->assertSame($notification, $result);
        $this->assertEquals('en_US', $notification->getLocale());
    }

    /**
     * Test locale method returns null when not set
     *
     * @return void
     */
    public function testLocaleMethodReturnsNullWhenNotSet(): void
    {
        $notification = new TestNotification();

        $this->assertNull($notification->getLocale());
    }

    /**
     * Test onQueue method sets and returns queue name
     *
     * @return void
     */
    public function testOnQueueMethodSetsAndReturnsQueueName(): void
    {
        $notification = new TestNotification();
        $result = $notification->onQueue('high-priority');

        $this->assertSame($notification, $result);
        $this->assertEquals('high-priority', $notification->getQueue());
    }

    /**
     * Test getQueue returns null when not set
     *
     * @return void
     */
    public function testGetQueueReturnsNullWhenNotSet(): void
    {
        $notification = new TestNotification();

        $this->assertNull($notification->getQueue());
    }

    /**
     * Test onConnection method sets and returns connection name
     *
     * @return void
     */
    public function testOnConnectionMethodSetsAndReturnsConnectionName(): void
    {
        $notification = new TestNotification();
        $result = $notification->onConnection('redis');

        $this->assertSame($notification, $result);
        $this->assertEquals('redis', $notification->getConnection());
    }

    /**
     * Test getConnection returns null when not set
     *
     * @return void
     */
    public function testGetConnectionReturnsNullWhenNotSet(): void
    {
        $notification = new TestNotification();

        $this->assertNull($notification->getConnection());
    }

    /**
     * Test delay method sets and returns delay
     *
     * @return void
     */
    public function testDelayMethodSetsAndReturnsDelay(): void
    {
        $notification = new TestNotification();
        $result = $notification->delay(60);

        $this->assertSame($notification, $result);
        $this->assertEquals(60, $notification->getDelay());
    }

    /**
     * Test getDelay returns null when not set
     *
     * @return void
     */
    public function testGetDelayReturnsNullWhenNotSet(): void
    {
        $notification = new TestNotification();

        $this->assertNull($notification->getDelay());
    }

    /**
     * Test setId and getId methods
     *
     * @return void
     */
    public function testSetIdAndGetIdMethods(): void
    {
        $notification = new TestNotification();
        $notification->setId('test-id-123');

        $this->assertEquals('test-id-123', $notification->getId());
    }

    /**
     * Test getId returns null when not set
     *
     * @return void
     */
    public function testGetIdReturnsNullWhenNotSet(): void
    {
        $notification = new TestNotification();

        $this->assertNull($notification->getId());
    }

    /**
     * Test toDatabase returns array by default
     *
     * @return void
     */
    public function testToDatabaseReturnsArrayByDefault(): void
    {
        $notification = new TestNotification();
        $notifiable = new Entity(['id' => 1]);

        $result = $notification->toDatabase($notifiable);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test toArray returns empty array by default
     *
     * @return void
     */
    public function testToArrayReturnsEmptyArrayByDefault(): void
    {
        $notification = new TestNotification();
        $notifiable = new Entity(['id' => 1]);

        $result = $notification->toArray($notifiable);

        $this->assertEmpty($result);
    }

    /**
     * Test toArray works with AnonymousNotifiable
     *
     * @return void
     */
    public function testToArrayWorksWithAnonymousNotifiable(): void
    {
        $notification = new TestNotification();
        $notifiable = new AnonymousNotifiable();

        $result = $notification->toArray($notifiable);

        $this->assertEmpty($result);
    }

    /**
     * Test toMail returns null by default
     *
     * @return void
     */
    public function testToMailReturnsNullByDefault(): void
    {
        $notification = new TestNotification();
        $notifiable = new Entity(['id' => 1]);

        $result = $notification->toMail($notifiable);

        $this->assertNull($result);
    }

    /**
     * Test shouldSend returns true by default
     *
     * @return void
     */
    public function testShouldSendReturnsTrueByDefault(): void
    {
        $notification = new TestNotification();
        $notifiable = new Entity(['id' => 1]);

        $this->assertTrue($notification->shouldSend($notifiable, 'database'));
        $this->assertTrue($notification->shouldSend($notifiable, 'mail'));
    }

    /**
     * Test shouldSend works with AnonymousNotifiable
     *
     * @return void
     */
    public function testShouldSendWorksWithAnonymousNotifiable(): void
    {
        $notification = new TestNotification();
        $notifiable = new AnonymousNotifiable();

        $this->assertTrue($notification->shouldSend($notifiable, 'database'));
    }

    /**
     * Test method chaining
     *
     * @return void
     */
    public function testMethodChaining(): void
    {
        $notification = new TestNotification();
        $result = $notification
            ->locale('en_US')
            ->onQueue('high')
            ->onConnection('redis')
            ->delay(120);

        $this->assertSame($notification, $result);
        $this->assertEquals('en_US', $notification->getLocale());
        $this->assertEquals('high', $notification->getQueue());
        $this->assertEquals('redis', $notification->getConnection());
        $this->assertEquals(120, $notification->getDelay());
    }
}
