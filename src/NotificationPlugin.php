<?php
declare(strict_types=1);

namespace Crustum\Notification;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Crustum\Notification\Command\NotificationCommand;
use Crustum\PluginManifest\Manifest\ManifestInterface;
use Crustum\PluginManifest\Manifest\ManifestTrait;

/**
 * Plugin for Notification
 *
 * @uses \Crustum\PluginManifest\Manifest\ManifestTrait
 */
class NotificationPlugin extends BasePlugin implements ManifestInterface
{
    use ManifestTrait;

    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface<\Cake\Core\BasePlugin> $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
    }

    /**
     * Add commands for the plugin
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands = parent::console($commands);
        $commands->add('bake notification', NotificationCommand::class);

        return $commands;
    }

    /**
     * Get the manifest for the plugin.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function manifest(): array
    {
        $pluginPath = dirname(__DIR__);

        return array_merge(
            static::manifestMigrations(
                $pluginPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Migrations',
            ),
            static::manifestConfig(
                $pluginPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'notification.php.example',
                CONFIG . 'notification.php',
                false,
            ),
            static::manifestBootstrapAppend(
                "if (file_exists(CONFIG . 'notification.php')) {\n    Configure::load('notification', 'default');\n}",
                '// Notification Plugin Configuration',
            ),
            static::manifestStarRepo('Crustum/Notification'),
        );
    }
}
