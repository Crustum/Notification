<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Message;

use Cake\TestSuite\TestCase;
use Crustum\Notification\Message\Action;
use Crustum\Notification\Message\DatabaseMessage;

/**
 * PayloadTrait Test Case
 *
 * Tests the payload trait fluent builder methods via DatabaseMessage
 */
class PayloadTraitTest extends TestCase
{
    /**
     * Test title method
     *
     * @return void
     */
    public function testTitle(): void
    {
        $message = DatabaseMessage::new()
            ->title('Test Title');

        $data = $message->getData();

        $this->assertEquals('Test Title', $data['title']);
    }

    /**
     * Test message method
     *
     * @return void
     */
    public function testMessage(): void
    {
        $message = DatabaseMessage::new()
            ->message('Test Message');

        $data = $message->getData();

        $this->assertEquals('Test Message', $data['message']);
    }

    /**
     * Test type method
     *
     * @return void
     */
    public function testType(): void
    {
        $message = DatabaseMessage::new()
            ->type('success');

        $data = $message->getData();

        $this->assertEquals('success', $data['type']);
    }

    /**
     * Test actionUrl method
     *
     * @return void
     */
    public function testActionUrl(): void
    {
        $message = DatabaseMessage::new()
            ->actionUrl('/posts/123');

        $data = $message->getData();

        $this->assertEquals('/posts/123', $data['action_url']);
    }

    /**
     * Test icon method
     *
     * @return void
     */
    public function testIcon(): void
    {
        $message = DatabaseMessage::new()
            ->icon('envelope');

        $data = $message->getData();

        $this->assertEquals('envelope', $data['icon']);
    }

    /**
     * Test iconClass method
     *
     * @return void
     */
    public function testIconClass(): void
    {
        $message = DatabaseMessage::new()
            ->iconClass('fa fa-check');

        $data = $message->getData();

        $this->assertEquals('fa fa-check', $data['icon_class']);
    }

    /**
     * Test actions method with Action objects
     *
     * @return void
     */
    public function testActionsWithActionObjects(): void
    {
        $action1 = Action::new('view')
            ->label('View')
            ->url('/posts/123');

        $action2 = Action::new('edit')
            ->label('Edit')
            ->url('/posts/123/edit');

        $message = DatabaseMessage::new()
            ->actions([$action1, $action2]);

        $data = $message->getData();

        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(2, $data['actions']);
        $this->assertEquals('view', $data['actions'][0]['name']);
        $this->assertEquals('View', $data['actions'][0]['label']);
        $this->assertEquals('edit', $data['actions'][1]['name']);
        $this->assertEquals('Edit', $data['actions'][1]['label']);
    }

    /**
     * Test actions method with arrays
     *
     * @return void
     */
    public function testActionsWithArrays(): void
    {
        $actions = [
            ['name' => 'view', 'label' => 'View', 'url' => '/posts/123'],
            ['name' => 'edit', 'label' => 'Edit', 'url' => '/posts/123/edit'],
        ];

        $message = DatabaseMessage::new()
            ->actions($actions);

        $data = $message->getData();

        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(2, $data['actions']);
        $this->assertEquals($actions, $data['actions']);
    }

    /**
     * Test actions method with mixed Action objects and arrays
     *
     * @return void
     */
    public function testActionsWithMixedTypes(): void
    {
        $action = Action::new('view')
            ->label('View')
            ->url('/posts/123');

        $actions = [
            $action,
            ['name' => 'edit', 'label' => 'Edit', 'url' => '/posts/123/edit'],
        ];

        $message = DatabaseMessage::new()
            ->actions($actions);

        $data = $message->getData();

        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(2, $data['actions']);
        $this->assertIsArray($data['actions'][0]);
        $this->assertEquals('view', $data['actions'][0]['name']);
        $this->assertEquals($actions[1], $data['actions'][1]);
    }

    /**
     * Test addAction method with Action object
     *
     * @return void
     */
    public function testAddActionWithActionObject(): void
    {
        $action = Action::new('view')
            ->label('View')
            ->url('/posts/123');

        $message = DatabaseMessage::new()
            ->addAction($action);

        $data = $message->getData();

        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(1, $data['actions']);
        $this->assertEquals('view', $data['actions'][0]['name']);
        $this->assertEquals('View', $data['actions'][0]['label']);
    }

    /**
     * Test addAction method with array
     *
     * @return void
     */
    public function testAddActionWithArray(): void
    {
        $action = ['name' => 'view', 'label' => 'View', 'url' => '/posts/123'];

        $message = DatabaseMessage::new()
            ->addAction($action);

        $data = $message->getData();

        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(1, $data['actions']);
        $this->assertEquals($action, $data['actions'][0]);
    }

    /**
     * Test addAction method multiple times
     *
     * @return void
     */
    public function testAddActionMultipleTimes(): void
    {
        $action1 = Action::new('view')
            ->label('View');

        $action2 = Action::new('edit')
            ->label('Edit');

        $message = DatabaseMessage::new()
            ->addAction($action1)
            ->addAction($action2);

        $data = $message->getData();

        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(2, $data['actions']);
        $this->assertEquals('view', $data['actions'][0]['name']);
        $this->assertEquals('edit', $data['actions'][1]['name']);
    }

    /**
     * Test method chaining
     *
     * @return void
     */
    public function testMethodChaining(): void
    {
        $message = DatabaseMessage::new()
            ->title('Test Title')
            ->message('Test Message')
            ->type('info')
            ->actionUrl('/posts/123')
            ->icon('envelope')
            ->iconClass('fa fa-envelope');

        $this->assertInstanceOf(DatabaseMessage::class, $message);

        $data = $message->getData();

        $this->assertEquals('Test Title', $data['title']);
        $this->assertEquals('Test Message', $data['message']);
        $this->assertEquals('info', $data['type']);
        $this->assertEquals('/posts/123', $data['action_url']);
        $this->assertEquals('envelope', $data['icon']);
        $this->assertEquals('fa fa-envelope', $data['icon_class']);
    }

    /**
     * Test that methods overwrite previous values
     *
     * @return void
     */
    public function testMethodsOverwritePreviousValues(): void
    {
        $message = DatabaseMessage::new()
            ->title('Original Title')
            ->message('Original Message')
            ->title('New Title')
            ->message('New Message');

        $data = $message->getData();

        $this->assertEquals('New Title', $data['title']);
        $this->assertEquals('New Message', $data['message']);
    }

    /**
     * Test with all methods combined
     *
     * @return void
     */
    public function testWithAllMethodsCombined(): void
    {
        $action1 = Action::new('view')
            ->label('View')
            ->url('/posts/123');

        $action2 = Action::new('edit')
            ->label('Edit')
            ->url('/posts/123/edit');

        $message = DatabaseMessage::new()
            ->title('Test Title')
            ->message('Test Message')
            ->type('success')
            ->actionUrl('/posts/123')
            ->icon('envelope')
            ->iconClass('fa fa-envelope')
            ->actions([$action1, $action2]);

        $data = $message->getData();

        $this->assertEquals('Test Title', $data['title']);
        $this->assertEquals('Test Message', $data['message']);
        $this->assertEquals('success', $data['type']);
        $this->assertEquals('/posts/123', $data['action_url']);
        $this->assertEquals('envelope', $data['icon']);
        $this->assertEquals('fa fa-envelope', $data['icon_class']);
        $this->assertCount(2, $data['actions']);
    }
}
