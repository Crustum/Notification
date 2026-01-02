<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Command\NotificationCommand;
use ReflectionClass;

/**
 * NotificationCommand Test Case
 */
class NotificationCommandTest extends TestCase
{
    /**
     * Set up test case
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test defaultName returns correct command name
     *
     * @return void
     */
    public function testDefaultNameReturnsCorrectCommandName(): void
    {
        $this->assertEquals('bake notification', NotificationCommand::defaultName());
    }

    /**
     * Test name returns correct name
     *
     * @return void
     */
    public function testNameReturnsCorrectName(): void
    {
        $command = new NotificationCommand();
        $this->assertEquals('notification', $command->name());
    }

    /**
     * Test getNotificationNameFromClass removes Notification suffix
     *
     * @return void
     */
    public function testGetNotificationNameFromClassRemovesSuffix(): void
    {
        $command = new NotificationCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('getNotificationNameFromClass');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'UserMentionedNotification');
        $this->assertEquals('User Mentioned', $result);
    }

    /**
     * Test getNotificationNameFromClass handles underscore names
     *
     * @return void
     */
    public function testGetNotificationNameFromClassHandlesUnderscoreNames(): void
    {
        $command = new NotificationCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('getNotificationNameFromClass');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'OrderShippedNotification');
        $this->assertEquals('Order Shipped', $result);
    }

    /**
     * Test getAvailableChannels returns builtin channels
     *
     * @return void
     */
    public function testGetAvailableChannelsReturnsBuiltinChannels(): void
    {
        $command = new NotificationCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('getAvailableChannels');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('mail', $result);
        $this->assertTrue($result['database']['builtin']);
        $this->assertTrue($result['mail']['builtin']);
    }

    /**
     * Test getSelectedChannels with channels option
     *
     * @return void
     */
    public function testGetSelectedChannelsWithChannelsOption(): void
    {
        $command = new NotificationCommand();
        $args = new Arguments([], ['channels' => 'database,mail'], []);
        $io = $this->createStub(ConsoleIo::class);

        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('getSelectedChannels');
        $method->setAccessible(true);

        $result = $method->invoke($command, $args, $io);

        $this->assertContains('database', $result);
        $this->assertContains('mail', $result);
    }

    /**
     * Test getSelectedChannels with all option
     *
     * @return void
     */
    public function testGetSelectedChannelsWithAllOption(): void
    {
        $command = new NotificationCommand();
        $args = new Arguments([], ['channels' => 'all'], []);
        $io = $this->createStub(ConsoleIo::class);

        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('getSelectedChannels');
        $method->setAccessible(true);

        $result = $method->invoke($command, $args, $io);

        $this->assertContains('database', $result);
        $this->assertContains('mail', $result);
    }

    /**
     * Test findChannelTemplate returns null for builtin channels
     *
     * @return void
     */
    public function testFindChannelTemplateReturnsNullForBuiltinChannels(): void
    {
        $command = new NotificationCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('findChannelTemplate');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'database');
        $this->assertNull($result);

        $result = $method->invoke($command, 'mail');
        $this->assertNull($result);
    }

    /**
     * Test findChannelImports returns empty array for builtin channels
     *
     * @return void
     */
    public function testFindChannelImportsReturnsEmptyArrayForBuiltinChannels(): void
    {
        $command = new NotificationCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('findChannelImports');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'database');
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        $result = $method->invoke($command, 'mail');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test discoverChannelData includes builtin channels
     *
     * @return void
     */
    public function testDiscoverChannelDataIncludesBuiltinChannels(): void
    {
        $command = new NotificationCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('discoverChannelData');
        $method->setAccessible(true);

        $result = $method->invoke($command, ['database', 'mail']);

        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('mail', $result);
    }

    /**
     * Test buildOptionParser sets description
     *
     * @return void
     */
    public function testBuildOptionParserSetsDescription(): void
    {
        $command = new NotificationCommand();
        $parser = $command->getOptionParser();

        $this->assertStringContainsString('Bake Notification class', $parser->getDescription());
    }

    /**
     * Test buildOptionParser adds channels option
     *
     * @return void
     */
    public function testBuildOptionParserAddsChannelsOption(): void
    {
        $command = new NotificationCommand();
        $parser = $command->getOptionParser();
        $options = $parser->options();

        $this->assertArrayHasKey('channels', $options);
    }

    /**
     * Test pathFragment is set correctly
     *
     * @return void
     */
    public function testPathFragmentIsSetCorrectly(): void
    {
        $command = new NotificationCommand();
        $this->assertEquals('Notification/', $command->pathFragment);
    }

    /**
     * Test execute returns error when name is empty
     *
     * @return void
     */
    public function testExecuteReturnsErrorWhenNameIsEmpty(): void
    {
        $command = new NotificationCommand();
        $args = new Arguments([], [], []);
        $io = $this->createMock(ConsoleIo::class);

        $io->expects($this->once())
            ->method('err')
            ->with($this->stringContains('You must provide a notification name'));

        $io->expects($this->once())
            ->method('out')
            ->with($this->stringContains('Example:'));

        $result = $command->execute($args, $io);

        $this->assertEquals(NotificationCommand::CODE_ERROR, $result);
    }
}
