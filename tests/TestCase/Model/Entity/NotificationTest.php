<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Model\Entity;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Model\Entity\Notification;

/**
 * Notification Entity Test Case
 *
 * Tests the Notification entity's virtual properties and methods
 */
class NotificationTest extends TestCase
{
    /**
     * Test that is_read returns false when read_at is null
     *
     * @return void
     */
    public function testIsReadWhenReadAtIsNull(): void
    {
        $notification = new Notification(['read_at' => null]);

        $this->assertFalse($notification->is_read);
    }

    /**
     * Test that is_read returns true when read_at is set
     *
     * @return void
     */
    public function testIsReadWhenReadAtIsSet(): void
    {
        $notification = new Notification(['read_at' => new DateTime()]);

        $this->assertTrue($notification->is_read);
    }

    /**
     * Test that is_unread returns true when read_at is null
     *
     * @return void
     */
    public function testIsUnreadWhenReadAtIsNull(): void
    {
        $notification = new Notification(['read_at' => null]);

        $this->assertTrue($notification->is_unread);
    }

    /**
     * Test that is_unread returns false when read_at is set
     *
     * @return void
     */
    public function testIsUnreadWhenReadAtIsSet(): void
    {
        $notification = new Notification(['read_at' => new DateTime()]);

        $this->assertFalse($notification->is_unread);
    }

    /**
     * Test that markAsRead sets read_at timestamp when notification is unread
     *
     * @return void
     */
    public function testMarkAsReadWhenUnread(): void
    {
        $notification = new Notification(['read_at' => null]);

        $result = $notification->markAsRead();

        $this->assertTrue($result);
        $this->assertInstanceOf(DateTime::class, $notification->read_at);
        $this->assertTrue($notification->is_read);
    }

    /**
     * Test that markAsRead returns false when notification is already read
     *
     * @return void
     */
    public function testMarkAsReadWhenAlreadyRead(): void
    {
        $readAt = new DateTime();
        $notification = new Notification(['read_at' => $readAt]);

        $result = $notification->markAsRead();

        $this->assertFalse($result);
        $this->assertSame($readAt, $notification->read_at);
    }

    /**
     * Test that markAsUnread clears read_at timestamp when notification is read
     *
     * @return void
     */
    public function testMarkAsUnreadWhenRead(): void
    {
        $notification = new Notification(['read_at' => new DateTime()]);

        $result = $notification->markAsUnread();

        $this->assertTrue($result);
        $this->assertNull($notification->read_at);
        $this->assertTrue($notification->is_unread);
    }

    /**
     * Test that markAsUnread returns false when notification is already unread
     *
     * @return void
     */
    public function testMarkAsUnreadWhenAlreadyUnread(): void
    {
        $notification = new Notification(['read_at' => null]);

        $result = $notification->markAsUnread();

        $this->assertFalse($result);
        $this->assertNull($notification->read_at);
    }
}
