<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase;

use Cake\Console\CommandCollection;
use Cake\Console\CommandInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\TestSuite\TestCase;
use Crustum\Notification\Command\NotificationCommand;
use Crustum\Notification\NotificationPlugin;

/**
 * NotificationPlugin Test Case
 *
 * Tests for the NotificationPlugin class
 */
class NotificationPluginTest extends TestCase
{
    /**
     * Test bootstrap method
     *
     * @return void
     */
    public function testBootstrap(): void
    {
        $app = $this->createStub(PluginApplicationInterface::class);
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
        $testCommand = $this->createStub(CommandInterface::class);
        $commands->add('test', $testCommand);
        $plugin = new NotificationPlugin();

        $result = $plugin->console($commands);

        $this->assertSame($commands, $result);
        $this->assertTrue($commands->has('test'));
        $this->assertTrue($commands->has('bake notification'));
    }

    /**
     * Test manifest returns array
     *
     * @return void
     */
    public function testManifestReturnsArray(): void
    {
        $manifest = NotificationPlugin::manifest();

        $this->assertNotEmpty($manifest);
    }

    /**
     * Test manifest includes migrations
     *
     * @return void
     */
    public function testManifestIncludesMigrations(): void
    {
        $manifest = NotificationPlugin::manifest();

        $hasMigrations = false;
        foreach ($manifest as $item) {
            if (isset($item['tag']) && $item['tag'] === 'migrations') {
                $hasMigrations = true;
                break;
            }
        }

        $this->assertTrue($hasMigrations);
    }

    /**
     * Test manifest includes config
     *
     * @return void
     */
    public function testManifestIncludesConfig(): void
    {
        $manifest = NotificationPlugin::manifest();

        $hasConfig = false;
        foreach ($manifest as $item) {
            if (isset($item['type']) && ($item['type'] === 'config' || isset($item['source']))) {
                $hasConfig = true;
                break;
            }
        }

        $this->assertTrue($hasConfig);
    }

    /**
     * Test manifest includes bootstrap append
     *
     * @return void
     */
    public function testManifestIncludesBootstrapAppend(): void
    {
        $manifest = NotificationPlugin::manifest();

        $hasBootstrap = false;
        foreach ($manifest as $item) {
            if (isset($item['type']) && $item['type'] === 'append') {
                $hasBootstrap = true;
                break;
            }
            if (isset($item['tag']) && $item['tag'] === 'bootstrap') {
                $hasBootstrap = true;
                break;
            }
        }

        $this->assertTrue($hasBootstrap);
    }

    /**
     * Test manifest includes star repo
     *
     * @return void
     */
    public function testManifestIncludesStarRepo(): void
    {
        $manifest = NotificationPlugin::manifest();

        $hasStarRepo = false;
        foreach ($manifest as $item) {
            if (isset($item['tag']) && $item['tag'] === 'star_repo') {
                $hasStarRepo = true;
                break;
            }
            if (isset($item['repo']) && str_contains($item['repo'], 'Crustum/Notification')) {
                $hasStarRepo = true;
                break;
            }
        }

        $this->assertTrue($hasStarRepo);
    }
}
