<?php
declare(strict_types=1);

namespace Crustum\Notification\Registry;

use BadMethodCallException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Crustum\Notification\Channel\ChannelInterface;

/**
 * Channel Registry
 *
 * An object registry for notification channel engines.
 * Used by NotificationManager to load and manage notification channels.
 *
 * @extends \Cake\Core\ObjectRegistry<\Crustum\Notification\Channel\ChannelInterface>
 */
class ChannelRegistry extends ObjectRegistry
{
    /**
     * Flag to track if discovery event has been dispatched
     *
     * @var bool
     */
    protected static bool $_discoveryDispatched = false;

    /**
     * Dispatch channel discovery event
     *
     * Allows external channel providers to register their channels by listening
     * to the 'Notification.Registry.discover' event. Only dispatches once per request.
     *
     * @return void
     */
    public function dispatchDiscoveryEvent(): void
    {
        if (static::$_discoveryDispatched) {
            return;
        }

        EventManager::instance()->dispatch(
            new Event('Notification.Registry.discover', $this),
        );

        static::$_discoveryDispatched = true;
    }

    /**
     * Resolve a channel class name
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve
     * @return class-string<\Crustum\Notification\Channel\ChannelInterface>|null Either the correct classname or null
     */
    protected function _resolveClassName(string $class): ?string
    {
        /** @var class-string<\Crustum\Notification\Channel\ChannelInterface>|null */
        return App::className($class, 'Channel', 'Channel');
    }

    /**
     * Throws an exception when a channel is missing
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing
     * @param string|null $plugin The plugin the channel is missing in
     * @return void
     * @throws \BadMethodCallException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        throw new BadMethodCallException(sprintf('Notification channel `%s` is not available.', $class));
    }

    /**
     * Create the channel instance
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param \Crustum\Notification\Channel\ChannelInterface|class-string<\Crustum\Notification\Channel\ChannelInterface> $class The classname or object to make
     * @param string $alias The alias of the object
     * @param array<string, mixed> $config An array of settings to use for the channel
     * @return \Crustum\Notification\Channel\ChannelInterface The constructed ChannelInterface class
     */
    protected function _create(object|string $class, string $alias, array $config): ChannelInterface
    {
        if (is_object($class)) {
            return $class;
        }

        return new $class($config);
    }

    /**
     * Remove a single channel from the registry
     *
     * @param string $name The channel name
     * @return $this
     */
    public function unload(string $name)
    {
        unset($this->_loaded[$name]);

        return $this;
    }
}
