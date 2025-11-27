<?php
declare(strict_types=1);

namespace Crustum\Notification\Command;

use Bake\Command\BakeCommand;
use Bake\Utility\TemplateRenderer;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Utility\Inflector;
use Crustum\Notification\Registry\ChannelRegistry;
use Exception;

/**
 * Command for generating Notification classes
 *
 * Generates notification classes that extend the base Notification class
 * for sending notifications through various channels (database, mail, etc.).
 *
 * Usage:
 * ```
 * bin/cake bake notification UserMentioned
 * bin/cake bake notification OrderShipped
 * ```
 */
class NotificationCommand extends BakeCommand
{
    /**
     * Path to Notification directory
     *
     * @var string
     */
    public string $pathFragment = 'Notification/';

    /**
     * Execute the command
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $name = $args->getArgumentAt(0);

        if (empty($name)) {
            $io->err('<error>You must provide a notification name.</error>');
            $io->out('Example: bin/cake bake notification UserMentioned');

            return static::CODE_ERROR;
        }

        $name = $this->_getName($name);

        if (!str_ends_with($name, 'Notification')) {
            $name .= 'Notification';
        }

        $content = $this->getContent($name, $args, $io);

        if (empty($content)) {
            $io->err("<warning>No generated content for '{$name}', not generating template.</warning>");

            return static::CODE_ERROR;
        }

        $this->bake($name, $args, $io, $content);

        return static::CODE_SUCCESS;
    }

    /**
     * Assembles and writes the notification file
     *
     * @param string $name Notification name
     * @param \Cake\Console\Arguments $args CLI arguments
     * @param \Cake\Console\ConsoleIo $io Console io
     * @param string|true $content Content to write
     * @return void
     */
    public function bake(string $name, Arguments $args, ConsoleIo $io, string|bool $content): void
    {
        $path = $this->getPath($args);
        $filename = $path . $name . '.php';
        $io->out("\n" . sprintf('Baking notification class for %s...', $name), 1, ConsoleIo::QUIET);

        if (is_string($content) && $args->getOption('verbose')) {
            $io->out($content);
        }

        if (is_string($content)) {
            $forceOption = $args->getOption('force');
            $force = is_bool($forceOption) ? $forceOption : false;
            $io->createFile($filename, $content, $force);
        }

        $emptyFile = $path . '.gitkeep';
        $this->deleteEmptyFile($emptyFile, $io);
    }

    /**
     * Get content for notification class
     *
     * @param string $name Notification name
     * @param \Cake\Console\Arguments $args CLI arguments
     * @param \Cake\Console\ConsoleIo $io Console io
     * @return string|bool Generated content
     */
    public function getContent(string $name, Arguments $args, ConsoleIo $io): string|bool
    {
        $namespace = Configure::read('App.namespace');
        if ($this->plugin) {
            $namespace = $this->_pluginNamespace($this->plugin);
        }

        $notificationName = $this->getNotificationNameFromClass($name);
        $selectedChannels = $this->getSelectedChannels($args, $io);
        $channelData = $this->discoverChannelData($selectedChannels);

        $vars = [
            'namespace' => $namespace,
            'class' => $name,
            'notificationName' => $notificationName,
            'channels' => $selectedChannels,
            'channelData' => $channelData,
        ];

        $themeOption = $args->getOption('theme');
        $theme = is_string($themeOption) ? $themeOption : null;
        $renderer = new TemplateRenderer($theme);
        $renderer->set('plugin', $this->plugin);
        $renderer->set($vars);

        return $renderer->generate('Crustum/Notification.Notification/notification');
    }

    /**
     * Gets the path for output
     *
     * Checks the plugin property and returns the correct path.
     *
     * @param \Cake\Console\Arguments $args Arguments instance to read the prefix option from
     * @return string Path to output
     */
    public function getPath(Arguments $args): string
    {
        $path = APP . $this->pathFragment;
        if ($this->plugin) {
            $path = $this->_pluginPath($this->plugin) . 'src/' . $this->pathFragment;
        }
        $prefix = $this->getPrefix($args);
        if ($prefix) {
            $path .= $prefix . DIRECTORY_SEPARATOR;
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Gets the option parser instance and configures it
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to configure
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = $this->_setCommonOptions($parser);
        $parser->setDescription('Bake Notification class.')
            ->addArgument('name', [
                'help' => 'Name of the notification class to generate (e.g., UserMentioned, OrderShipped). "Notification" suffix will be added automatically.',
                'required' => true,
            ])
            ->addOption('channels', [
                'help' => 'Comma-separated list of channels to include (e.g., database,slack,telegram). Use "all" for all available channels.',
                'short' => 'n',
                'default' => null,
            ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'bake notification';
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'notification';
    }

    /**
     * Get the notification name from the class name
     *
     * @param string $className Class name
     * @return string Notification name
     */
    protected function getNotificationNameFromClass(string $className): string
    {
        $name = str_replace('Notification', '', $className);

        return Inflector::humanize(Inflector::underscore($name));
    }

    /**
     * Get selected channels from arguments or user input
     *
     * @param \Cake\Console\Arguments $args CLI arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return array<string> Selected channel names
     */
    protected function getSelectedChannels(Arguments $args, ConsoleIo $io): array
    {
        $channelsOption = (string)$args->getOption('channels');

        if ($channelsOption) {
            if ($channelsOption === 'all') {
                return array_keys($this->getAvailableChannels());
            }

            return array_map('trim', explode(',', $channelsOption));
        }

        $available = $this->getAvailableChannels();
        $channelNames = array_keys($available);

        $io->out('<info>Available channels:</info> ' . implode(', ', $channelNames));
        $selection = $io->ask('Select channels (comma-separated or "all")', 'database');

        if ($selection === 'all') {
            return $channelNames;
        }

        return array_map('trim', explode(',', $selection));
    }

    /**
     * Get available notification channels
     *
     * @return array<string, array<string, mixed>> Available channels with metadata
     */
    protected function getAvailableChannels(): array
    {
        $channels = [
            'database' => ['builtin' => true],
            'mail' => ['builtin' => true],
        ];

        $registry = new ChannelRegistry();
        $event = new Event('Notification.Registry.discover', $registry);
        EventManager::instance()->dispatch($event);

        $registeredChannels = $registry->loaded();

        foreach ($registeredChannels as $channelName) {
            if (!isset($channels[$channelName])) {
                $channels[$channelName] = ['discovered' => true];
            }
        }

        return $channels;
    }

    /**
     * Discover channel data including templates and imports
     *
     * @param array<string> $selectedChannels Selected channel names
     * @return array<string, array<string, mixed>> Channel data with templates and imports
     */
    protected function discoverChannelData(array $selectedChannels): array
    {
        $channelData = [];

        foreach ($selectedChannels as $channelName) {
            $templatePath = $this->findChannelTemplate($channelName);
            $imports = $this->findChannelImports($channelName);

            if ($templatePath || in_array($channelName, ['database', 'mail'])) {
                $channelData[$channelName] = [
                    'templatePath' => $templatePath,
                    'imports' => $imports,
                ];
            }
        }

        return $channelData;
    }

    /**
     * Find channel template
     *
     * @param string $channelName Channel name
     * @return string|null Template path
     */
    protected function findChannelTemplate(string $channelName): ?string
    {
        if (in_array($channelName, ['database', 'mail'])) {
            return null;
        }

        $plugins = Plugin::loaded();

        foreach ($plugins as $pluginName) {
            try {
                $plugin = Plugin::getCollection()->get($pluginName);
                $templateFile = 'method_' . $channelName . '.twig';
                $templatePath = $plugin->getPath() . 'templates' . DS . 'bake' . DS . 'Notification' . DS . $templateFile;

                if (file_exists($templatePath)) {
                    return $templatePath;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Find channel imports
     *
     * @param string $channelName Channel name
     * @return array<string>
     */
    protected function findChannelImports(string $channelName): array
    {
        if (in_array($channelName, ['database', 'mail'])) {
            return [];
        }

        $plugins = Plugin::loaded();

        foreach ($plugins as $pluginName) {
            try {
                $plugin = Plugin::getCollection()->get($pluginName);
                $importsFile = 'imports_' . $channelName . '.twig';
                $importsPath = $plugin->getPath() . 'templates' . DS . 'bake' . DS . 'Notification' . DS . $importsFile;

                if (file_exists($importsPath)) {
                    $content = file_get_contents($importsPath);
                    if ($content === false) {
                        continue;
                    }

                    return array_filter(array_map('trim', explode("\n", $content)));
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return [];
    }
}
