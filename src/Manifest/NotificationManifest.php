<?php
declare(strict_types=1);

namespace Crustum\Notification\Manifest;

use Crustum\Notification\NotificationPlugin;
use Crustum\PluginManifest\Manifest\ManifestOps;

/**
 * Standalone manifest provider for the Notification plugin.
 *
 * Plain class without interface or trait dependencies, so the plugin loads
 * and runs without `crustum/plugin-manifest` installed. Discovered by the
 * installer through the provider convention when the plugin class itself
 * provides no manifest. All package references stay inside method bodies,
 * which only execute when the installer (and therefore the package) exists.
 */
final class NotificationManifest
{
    /**
     * Plugin install assets via crustum/plugin-manifest.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function manifest(): array
    {
        $pluginPath = dirname(__DIR__, 2);
        $plugin = ManifestOps::pluginName(NotificationPlugin::class);

        return array_merge(
            ManifestOps::manifestMigrations(
                $plugin,
                $pluginPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Migrations',
            ),
            ManifestOps::manifestConfig(
                $plugin,
                $pluginPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'notification.php.example',
                CONFIG . 'notification.php',
                false,
            ),
            ManifestOps::manifestBootstrapAppend(
                $plugin,
                "if (file_exists(CONFIG . 'notification.php')) {\n    Configure::load('notification', 'default');\n}",
                '// Notification Plugin Configuration',
            ),
            ManifestOps::manifestStarRepo($plugin, 'Crustum/Notification'),
        );
    }
}
