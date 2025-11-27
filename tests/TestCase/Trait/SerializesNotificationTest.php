<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Trait;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Test\TestCase\Trait\TestNotification\TestNotificationComplex;
use Crustum\Notification\Test\TestCase\Trait\TestNotification\TestNotificationWithArray;
use Crustum\Notification\Test\TestCase\Trait\TestNotification\TestNotificationWithDateTime;
use Crustum\Notification\Test\TestCase\Trait\TestNotification\TestNotificationWithEntity;
use Crustum\Notification\Test\TestCase\Trait\TestNotification\TestNotificationWithNullable;
use Crustum\Notification\Test\TestCase\Trait\TestNotification\TestNotificationWithScalars;
use DateTime;

/**
 * SerializesNotification Trait Test
 *
 * Tests automatic serialization with various property types
 */
class SerializesNotificationTest extends TestCase
{
    /**
     * Test serialization with scalar properties
     *
     * @return void
     */
    public function testSerializesScalarProperties(): void
    {
        $notification = new TestNotificationWithScalars('Test Title', 123, true);

        $data = $notification->__serialize();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('__class__', $data);
        $this->assertStringContainsString('TestNotificationWithScalars', $data['__class__']);
    }

    /**
     * Test unserialization restores scalar properties
     *
     * @return void
     */
    public function testUnserializesScalarProperties(): void
    {
        $original = new TestNotificationWithScalars('Test Title', 123, true);

        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $this->assertEquals('Test Title', $restored->getTitle());
        $this->assertEquals(123, $restored->getCount());
        $this->assertTrue($restored->isActive());
    }

    /**
     * Test serialization with array properties
     *
     * @return void
     */
    public function testSerializesArrayProperties(): void
    {
        $notification = new TestNotificationWithArray(['key1' => 'value1', 'key2' => 'value2']);

        $data = $notification->__serialize();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('__class__', $data);
    }

    /**
     * Test serialization with DateTime objects
     *
     * @return void
     */
    public function testSerializesDateTimeObjects(): void
    {
        $date = new DateTime('2025-10-14 12:00:00');
        $notification = new TestNotificationWithDateTime($date);

        $data = $notification->__serialize();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('__class__', $data);
    }

    /**
     * Test serialization with Entity objects
     *
     * @return void
     */
    public function testSerializesEntityObjects(): void
    {
        $entity = new Entity(['id' => 1, 'title' => 'Test Post']);
        $entity->setSource('Posts');

        $notification = new TestNotificationWithEntity($entity);

        $data = $notification->__serialize();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('__class__', $data);
    }

    /**
     * Test unserialization restores Entity data as array
     *
     * @return void
     */
    public function testUnserializesEntityAsArray(): void
    {
        $entity = new Entity(['id' => 1, 'title' => 'Test Post']);
        $entity->setSource('Posts');

        $original = new TestNotificationWithEntity($entity);

        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $restoredPost = $restored->getPost();
        $this->assertIsArray($restoredPost);
        $this->assertEquals(1, $restoredPost['id']);
        $this->assertEquals('Test Post', $restoredPost['title']);
    }

    /**
     * Test serialization with null properties
     *
     * @return void
     */
    public function testSerializesNullProperties(): void
    {
        $notification = new TestNotificationWithNullable(null);

        $data = $notification->__serialize();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('__class__', $data);
    }

    /**
     * Test complete round-trip serialization
     *
     * @return void
     */
    public function testCompleteRoundTripSerialization(): void
    {
        $entity = new Entity(['id' => 1, 'title' => 'Test']);
        $entity->setSource('Posts');

        $original = new TestNotificationComplex(
            'Title',
            123,
            ['tag1', 'tag2'],
            $entity,
            new DateTime('2025-10-14 12:00:00'),
        );

        $serialized = serialize($original);
        $restored = unserialize($serialized);

        $this->assertEquals('Title', $restored->getTitle());
        $this->assertEquals(123, $restored->getEntityId());
        $this->assertEquals(['tag1', 'tag2'], $restored->getTags());
        $this->assertIsArray($restored->getPost());
        $this->assertEquals(1, $restored->getPost()['id']);
    }
}
