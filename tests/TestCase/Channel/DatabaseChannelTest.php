<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Channel;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Channel\DatabaseChannel;

/**
 * DatabaseChannel Test Case
 *
 * Tests the database channel functionality for storing notifications
 */
class DatabaseChannelTest extends TestCase
{
    /**
     * Fixtures to load
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Crustum/Notification.Notifications',
    ];

    /**
     * Test that send stores notification in database
     *
     * @return void
     */
    public function testSendStoresNotification(): void
    {
        $channel = new DatabaseChannel();
        $notification = new SimpleTestNotification();
        $notification->setId('123e4567-e89b-12d3-a456-426614174000');

        $entity = new Entity(['id' => 99]);
        $entity->setSource('Users');

        $result = $channel->send($entity, $notification);

        $this->assertNotFalse($result);
        $this->assertEquals('Users', $result->model);
        $this->assertEquals('99', $result->foreign_key);
        $this->assertEquals(SimpleTestNotification::class, $result->type);
        $this->assertEquals(['test' => 'data'], $result->data);
    }
}
