<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Message;

use Cake\TestSuite\TestCase;
use Crustum\Notification\Message\DatabaseMessage;

/**
 * DatabaseMessage Test Case
 *
 * Tests the database message data container
 */
class DatabaseMessageTest extends TestCase
{
    /**
     * Test constructor with empty array
     *
     * @return void
     */
    public function testConstructorWithEmptyArray(): void
    {
        $message = new DatabaseMessage();

        $this->assertInstanceOf(DatabaseMessage::class, $message);
        $this->assertEquals([], $message->getData());
    }

    /**
     * Test constructor with initial data
     *
     * @return void
     */
    public function testConstructorWithInitialData(): void
    {
        $data = [
            'title' => 'Test Title',
            'message' => 'Test Message',
        ];

        $message = new DatabaseMessage($data);

        $this->assertEquals($data, $message->getData());
    }

    /**
     * Test new factory method with empty array
     *
     * @return void
     */
    public function testNewWithEmptyArray(): void
    {
        $message = DatabaseMessage::new();

        $this->assertInstanceOf(DatabaseMessage::class, $message);
        $this->assertEquals([], $message->getData());
    }

    /**
     * Test new factory method with initial data
     *
     * @return void
     */
    public function testNewWithInitialData(): void
    {
        $data = [
            'title' => 'Test Title',
            'message' => 'Test Message',
        ];

        $message = DatabaseMessage::new($data);

        $this->assertEquals($data, $message->getData());
    }

    /**
     * Test data method replaces existing data
     *
     * @return void
     */
    public function testDataReplacesExistingData(): void
    {
        $message = DatabaseMessage::new(['title' => 'Original Title']);

        $newData = [
            'title' => 'New Title',
            'message' => 'New Message',
        ];

        $message->data($newData);

        $this->assertEquals($newData, $message->getData());
        $this->assertArrayNotHasKey('original', $message->getData());
    }

    /**
     * Test getData method returns data
     *
     * @return void
     */
    public function testGetData(): void
    {
        $data = [
            'title' => 'Test Title',
            'message' => 'Test Message',
            'type' => 'info',
        ];

        $message = DatabaseMessage::new($data);

        $this->assertEquals($data, $message->getData());
    }

    /**
     * Test toArray method returns data
     *
     * @return void
     */
    public function testToArray(): void
    {
        $data = [
            'title' => 'Test Title',
            'message' => 'Test Message',
        ];

        $message = DatabaseMessage::new($data);

        $this->assertEquals($data, $message->toArray());
    }

    /**
     * Test with complex nested data structures
     *
     * @return void
     */
    public function testWithComplexNestedData(): void
    {
        $data = [
            'title' => 'Test Title',
            'message' => 'Test Message',
            'metadata' => [
                'user' => [
                    'id' => 123,
                    'name' => 'John Doe',
                ],
                'tags' => ['tag1', 'tag2', 'tag3'],
            ],
            'actions' => [
                ['name' => 'view', 'url' => '/posts/123'],
                ['name' => 'edit', 'url' => '/posts/123/edit'],
            ],
        ];

        $message = DatabaseMessage::new($data);

        $result = $message->getData();

        $this->assertEquals($data, $result);
        $this->assertEquals('John Doe', $result['metadata']['user']['name']);
        $this->assertCount(2, $result['actions']);
    }

    /**
     * Test data method returns self for chaining
     *
     * @return void
     */
    public function testDataReturnsSelfForChaining(): void
    {
        $message = DatabaseMessage::new();

        $result = $message->data(['title' => 'Test']);

        $this->assertSame($message, $result);
    }

    /**
     * Test with empty array data
     *
     * @return void
     */
    public function testWithEmptyArrayData(): void
    {
        $message = DatabaseMessage::new([]);

        $this->assertEquals([], $message->getData());
        $this->assertEquals([], $message->toArray());
    }

    /**
     * Test with various data types
     *
     * @return void
     */
    public function testWithVariousDataTypes(): void
    {
        $data = [
            'string' => 'text',
            'integer' => 123,
            'float' => 45.67,
            'boolean' => true,
            'null' => null,
            'array' => ['nested' => 'value'],
        ];

        $message = DatabaseMessage::new($data);

        $result = $message->getData();

        $this->assertEquals($data, $result);
        $this->assertIsString($result['string']);
        $this->assertIsInt($result['integer']);
        $this->assertIsFloat($result['float']);
        $this->assertIsBool($result['boolean']);
        $this->assertNull($result['null']);
        $this->assertIsArray($result['array']);
    }
}
