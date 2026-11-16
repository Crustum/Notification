<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\Console\CommandCollection;
use Cake\Console\CommandInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Command\NotificationCommand;
use Crustum\Notification\NotificationPlugin;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;

/**
 * NotificationPlugin Test Case
 *
 * Tests for the NotificationPlugin class
 */
class NotificationPluginTest extends TestCase
{
    use VerifiesDoubles;

    /**
     * Test bootstrap method
     *
     * @return void
     */
    public function testBootstrap(): void
    {
        $app = Double::for(PluginApplicationInterface::class);
        $plugin = new NotificationPlugin();

        $plugin->bootstrap($app);

        $this->assertInstanceOf(NotificationPlugin::class, $plugin);
    }

    /**
     * Test console method adds notification command
     *
     * @return void
     */
    public function testConsoleAddsNotificationCommand(): void
    {
        $commands = new CommandCollection();
        $plugin = new NotificationPlugin();

        $result = $plugin->console($commands);

        $this->assertSame($commands, $result);
        $this->assertTrue($commands->has('bake notification'));
        $this->assertEquals(NotificationCommand::class, $commands->get('bake notification'));
    }

    /**
     * Test console method preserves existing commands
     *
     * @return void
     */
    public function testConsolePreservesExistingCommands(): void
    {
        $commands = new CommandCollection();
        $testCommand = Double::for(CommandInterface::class);
        $commands->add('test', $testCommand);
        $plugin = new NotificationPlugin();

        $result = $plugin->console($commands);

        $this->assertSame($commands, $result);
        $this->assertTrue($commands->has('test'));
        $this->assertTrue($commands->has('bake notification'));
    }
}
