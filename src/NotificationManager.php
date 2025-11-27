<?php
declare(strict_types=1);

namespace Crustum\Notification;

use Cake\Core\StaticConfigTrait;
use Cake\Datasource\EntityInterface;
use Crustum\Notification\Channel\ChannelInterface;
use Crustum\Notification\Channel\DatabaseChannel;
use Crustum\Notification\Channel\MailChannel;
use Crustum\Notification\Registry\ChannelRegistry;
use InvalidArgumentException;

/**
 * Notification Manager
 *
 * Static facade for managing notification channels and sending notifications.
 * Uses ChannelRegistry to load and manage channel instances.
 *
 * Usage:
 * ```
 * // Configure channels
 * NotificationManager::setConfig('database', [
 *     'className' => DatabaseChannel::class,
 * ]);
 *
 * // Send notification
 * NotificationManager::send($user, new WelcomeNotification());
 *
 * // Get channel instance
 * $channel = NotificationManager::channel('database');
 * ```
 */
class NotificationManager
{
    use StaticConfigTrait;

    /**
     * Notification Channel Registry used for creating and using channel instances
     *
     * @var \Crustum\Notification\Registry\ChannelRegistry
     */
    protected static ChannelRegistry $_registry;

    /**
     * Locale for notifications
     *
     * @var string|null
     */
    protected static ?string $_locale = null;

    /**
     * Notification sender class to use
     *
     * @var class-string<\Crustum\Notification\NotificationSender>|null
     */
    protected static ?string $_senderClass = null;

    /**
     * Returns the Channel Registry instance used for creating and using channel instances
     *
     * @return \Crustum\Notification\Registry\ChannelRegistry
     */
    public static function getRegistry(): ChannelRegistry
    {
        if (!isset(static::$_registry)) {
            static::$_registry = new ChannelRegistry();
            static::$_registry->dispatchDiscoveryEvent();
        }

        return static::$_registry;
    }

    /**
     * Sets the Channel Registry instance used for creating and using channel instances
     *
     * Also allows for injecting of a new registry instance.
     *
     * @param \Crustum\Notification\Registry\ChannelRegistry $registry Injectable registry object
     * @return void
     */
    public static function setRegistry(ChannelRegistry $registry): void
    {
        static::$_registry = $registry;
    }

    /**
     * Get a ChannelInterface object for the named notification channel
     *
     * Can be called with channel name or class name:
     * - NotificationManager::channel('database')
     * - NotificationManager::channel(DatabaseChannel::class)
     *
     * @param string $name The name or class name of the notification channel
     * @return \Crustum\Notification\Channel\ChannelInterface
     * @throws \InvalidArgumentException When channel configuration is missing
     */
    public static function channel(string $name): ChannelInterface
    {
        $registry = static::getRegistry();

        if (class_exists($name) && is_subclass_of($name, ChannelInterface::class)) {
            $className = $name;
            $name = static::_getChannelNameFromClass($className);

            if (!$registry->has($name)) {
                $registry->load($name, ['className' => $className]);
            }

            return $registry->get($name);
        }

        if ($registry->has($name)) {
            return $registry->get($name);
        }

        static::_buildChannel($name);

        return $registry->get($name);
    }

    /**
     * Build and load a notification channel
     *
     * @param string $name Name of the channel configuration
     * @return void
     * @throws \InvalidArgumentException When channel configuration is missing
     */
    protected static function _buildChannel(string $name): void
    {
        $registry = static::getRegistry();

        $defaultChannels = static::_getDefaultChannels();
        if (isset($defaultChannels[$name])) {
            $config = ['className' => $defaultChannels[$name]];
            $registry->load($name, $config);

            return;
        }

        if (empty(static::$_config[$name]['className'])) {
            throw new InvalidArgumentException(
                sprintf('The `%s` notification channel configuration does not exist.', $name),
            );
        }

        $config = static::$_config[$name];
        $registry->load($name, $config);
    }

    /**
     * Get default channel mappings
     *
     * @return array<string, class-string<\Crustum\Notification\Channel\ChannelInterface>>
     */
    protected static function _getDefaultChannels(): array
    {
        return [
            'database' => DatabaseChannel::class,
            'mail' => MailChannel::class,
        ];
    }

    /**
     * Get channel name from class name
     *
     * @param string $className Channel class name
     * @return string Channel name
     */
    protected static function _getChannelNameFromClass(string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = end($parts);

        return strtolower(str_replace('Channel', '', $shortName));
    }

    /**
     * Configure the sender class to use
     *
     * @param class-string<\Crustum\Notification\NotificationSender> $class Sender class name
     * @return void
     */
    public static function configureSender(string $class): void
    {
        static::$_senderClass = $class;
    }

    /**
     * Reset the sender class to default
     *
     * @return void
     */
    public static function resetSender(): void
    {
        static::$_senderClass = null;
    }

    /**
     * Get a sender instance
     *
     * @param string|null $locale Locale for notifications
     * @return \Crustum\Notification\NotificationSender
     */
    public static function getSender(?string $locale = null): NotificationSender
    {
        $class = static::$_senderClass ?? NotificationSender::class;

        return new $class($locale);
    }

    /**
     * Send the given notification to the given notifiable entities
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return void
     */
    public static function send(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification): void
    {
        static::getSender(static::$_locale)->send($notifiables, $notification);
    }

    /**
     * Send the given notification immediately
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable|iterable<\Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable> $notifiables The entity or entities to notify
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @param array<string>|null $channels Optional array of specific channels to use
     * @return void
     */
    public static function sendNow(EntityInterface|AnonymousNotifiable|iterable $notifiables, Notification $notification, ?array $channels = null): void
    {
        static::getSender(static::$_locale)->sendNow($notifiables, $notification, $channels);
    }

    /**
     * Set the locale for notifications
     *
     * @param string $locale Locale code
     * @return void
     */
    public static function locale(string $locale): void
    {
        static::$_locale = $locale;
    }

    /**
     * Get the list of configured channels
     *
     * @return list<int|string> List of channel names
     */
    public static function configured(): array
    {
        return array_keys(static::$_config);
    }

    /**
     * Create an AnonymousNotifiable with routing for a single channel
     *
     * ```
     * NotificationManager::route('broadcast', 'admin-channel')
     *     ->notify(new SystemAlert('Server down'));
     * ```
     *
     * @param string $channel Channel name
     * @param mixed $route Routing information
     * @return \Crustum\Notification\AnonymousNotifiable
     */
    public static function route(string $channel, mixed $route): AnonymousNotifiable
    {
        return (new AnonymousNotifiable())->route($channel, $route);
    }

    /**
     * Create an AnonymousNotifiable with routing for multiple channels
     *
     * ```
     * NotificationManager::routes([
     *     'broadcast' => 'admin-channel',
     *     'slack' => '#alerts',
     * ])->notify(new SystemAlert('Server down'));
     * ```
     *
     * @param array<string, mixed> $routes Channel routing information
     * @return \Crustum\Notification\AnonymousNotifiable
     */
    public static function routes(array $routes): AnonymousNotifiable
    {
        $anonymous = new AnonymousNotifiable();

        foreach ($routes as $channel => $route) {
            $anonymous->route($channel, $route);
        }

        return $anonymous;
    }
}
