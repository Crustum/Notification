<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Message;

use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Message\Action;

/**
 * Action Test Case
 *
 * Tests the notification action fluent builder
 */
class ActionTest extends TestCase
{
    /**
     * Test constructor
     *
     * @return void
     */
    public function testConstructor(): void
    {
        $action = new Action('view_post');

        $this->assertInstanceOf(Action::class, $action);
    }

    /**
     * Test new factory method
     *
     * @return void
     */
    public function testNew(): void
    {
        $action = Action::new('view_post');

        $this->assertInstanceOf(Action::class, $action);
    }

    /**
     * Test label method
     *
     * @return void
     */
    public function testLabel(): void
    {
        $action = Action::new('view_post')
            ->label('View Post');

        $array = $action->toArray();

        $this->assertEquals('View Post', $array['label']);
    }

    /**
     * Test url method with string
     *
     * @return void
     */
    public function testUrlWithString(): void
    {
        $action = Action::new('view_post')
            ->url('/posts/123');

        $array = $action->toArray();

        $this->assertEquals('/posts/123', $array['url']);
    }

    /**
     * Test url method with array
     *
     * @return void
     */
    public function testUrlWithArray(): void
    {
        Router::reload();
        $builder = Router::createRouteBuilder('/');
        $builder->connect('/posts/:id', ['controller' => 'Posts', 'action' => 'view'], ['id' => '\d+']);

        $action = Action::new('view_post')
            ->url(['controller' => 'Posts', 'action' => 'view', 'id' => 123]);

        $array = $action->toArray();

        $this->assertIsString($array['url']);
    }

    /**
     * Test type method
     *
     * @return void
     */
    public function testType(): void
    {
        $action = Action::new('view_post')
            ->type('primary');

        $array = $action->toArray();

        $this->assertEquals('primary', $array['type']);
    }

    /**
     * Test icon method
     *
     * @return void
     */
    public function testIcon(): void
    {
        $action = Action::new('view_post')
            ->icon('eye');

        $array = $action->toArray();

        $this->assertEquals('eye', $array['icon']);
    }

    /**
     * Test toArray with all properties
     *
     * @return void
     */
    public function testToArrayWithAllProperties(): void
    {
        $action = Action::new('view_post')
            ->label('View Post')
            ->url('/posts/123')
            ->type('primary')
            ->icon('eye');

        $array = $action->toArray();

        $this->assertEquals('view_post', $array['name']);
        $this->assertEquals('View Post', $array['label']);
        $this->assertEquals('/posts/123', $array['url']);
        $this->assertEquals('primary', $array['type']);
        $this->assertEquals('eye', $array['icon']);
    }

    /**
     * Test toArray with only name
     *
     * @return void
     */
    public function testToArrayWithOnlyName(): void
    {
        $action = Action::new('view_post');

        $array = $action->toArray();

        $this->assertEquals('view_post', $array['name']);
        $this->assertArrayNotHasKey('label', $array);
        $this->assertArrayNotHasKey('url', $array);
        $this->assertArrayNotHasKey('type', $array);
        $this->assertArrayNotHasKey('icon', $array);
    }

    /**
     * Test method chaining
     *
     * @return void
     */
    public function testMethodChaining(): void
    {
        $action = Action::new('view_post')
            ->label('View Post')
            ->url('/posts/123')
            ->type('primary')
            ->icon('eye');

        $this->assertInstanceOf(Action::class, $action);

        $array = $action->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('icon', $array);
    }

    /**
     * Test with empty string values
     *
     * @return void
     */
    public function testWithEmptyStringValues(): void
    {
        $action = Action::new('view_post')
            ->label('')
            ->url('')
            ->type('')
            ->icon('');

        $array = $action->toArray();

        $this->assertEquals('', $array['label']);
        $this->assertEquals('', $array['url']);
        $this->assertEquals('', $array['type']);
        $this->assertEquals('', $array['icon']);
    }

    /**
     * Test with special characters in label
     *
     * @return void
     */
    public function testWithSpecialCharactersInLabel(): void
    {
        $action = Action::new('view_post')
            ->label('View & Edit Post <script>');

        $array = $action->toArray();

        $this->assertEquals('View & Edit Post <script>', $array['label']);
    }
}
