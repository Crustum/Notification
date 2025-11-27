<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\TestSuite\TestCase;
use Crustum\Notification\AnonymousNotifiable;
use InvalidArgumentException;

/**
 * AnonymousNotifiable Test Case
 *
 * Tests the anonymous notifiable functionality for on-demand notifications
 */
class AnonymousNotifiableTest extends TestCase
{
    /**
     * Test that route sets routing information
     *
     * @return void
     */
    public function testRoutesSetsRoutingInformation(): void
    {
        $anonymous = new AnonymousNotifiable();
        $result = $anonymous->route('mail', 'admin@example.com');

        $this->assertSame($anonymous, $result);
        $this->assertEquals('admin@example.com', $anonymous->routeNotificationFor('mail'));
    }

    /**
     * Test that route throws exception for database channel
     *
     * @return void
     */
    public function testRouteThrowsExceptionForDatabaseChannel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The database channel does not support on-demand notifications.');

        $anonymous = new AnonymousNotifiable();
        $anonymous->route('database', 'something');
    }

    /**
     * Test getSource returns Anonymous
     *
     * @return void
     */
    public function testGetSourceReturnsAnonymous(): void
    {
        $anonymous = new AnonymousNotifiable();

        $this->assertEquals('Anonymous', $anonymous->getSource());
    }

    /**
     * Test get returns route value
     *
     * @return void
     */
    public function testGetReturnsRouteValue(): void
    {
        $anonymous = new AnonymousNotifiable();
        $anonymous->route('slack', 'webhook-url');

        $this->assertEquals('webhook-url', $anonymous->get('slack'));
        $this->assertNull($anonymous->get('nonexistent'));
    }
}
