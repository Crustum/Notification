<?php
declare(strict_types=1);

namespace Crustum\Notification;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Crustum\Notification\Channel\DatabaseChannel;
use Crustum\Notification\Channel\MailChannel;
use Crustum\Notification\Command\NotificationCommand;

/**
 * Plugin for Notification
 */
class NotificationPlugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        if (!Configure::check('Notification')) {
            if (file_exists(CONFIG . 'notification.php')) {
                Configure::load('notification', 'default');
            } elseif (file_exists($this->getConfigPath() . 'notification.php')) {
                Configure::load('Crustum/Notification.notification', 'default', false);
            }
        }

        static::ensureDefaultChannels();

        $descriptions = (array)Configure::read('DebugBar.inspectorDescriptions');
        $descriptions['Notifications'] ??= 'Notification deliveries grouped by recipient and channel.';
        Configure::write('DebugBar.inspectorDescriptions', $descriptions);
    }

    /**
     * Registers bundled and host-configured channels once.
     *
     * The plugin boots on every integration test request while channel
     * configs live in a process-global static — unconditional `setConfig()`
     * throws "Cannot reconfigure" on the second boot, so only missing
     * keys are filled. Host `Notification.channels` configure entries win
     * over the bundled defaults; host apps keep full override power by
     * configuring channels before the plugin boots. Channels shipped by
     * satellite plugins (e.g. `broadcast`) register themselves — they do
     * not belong to this plugin's defaults.
     *
     * @return void
     */
    protected static function ensureDefaultChannels(): void
    {
        $channels = [
            'database' => [
                'className' => DatabaseChannel::class,
            ],
            'mail' => [
                'className' => MailChannel::class,
                'profile' => 'default',
            ],
        ];

        $notification = (array)Configure::read('Notification');
        foreach ((array)($notification['channels'] ?? []) as $name => $channelConfig) {
            $channels[$name] = $channelConfig;
        }

        $configured = NotificationManager::configured();
        foreach ($channels as $name => $config) {
            if (!in_array($name, $configured, true)) {
                NotificationManager::setConfig($name, $config);
            }
        }
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
}
