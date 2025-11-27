<?php
declare(strict_types=1);

namespace Crustum\Notification\Extension;

use Crustum\Notification\Registry\ChannelRegistry;

/**
 * Channel Provider Interface
 *
 * Defines the contract for channel plugins to register themselves with the core
 * notification system. Channel providers enable a decentralized plugin ecosystem
 * where each notification channel can be developed, distributed, and maintained
 * independently.
 *
 * Example Usage:
 * ```
 * class TelegramChannelProvider implements ChannelProviderInterface
 * {
 *     public function provides(): array
 *     {
 *         return ['telegram'];
 *     }
 *
 *     public function register(ChannelRegistry $registry): void
 *     {
 *         $registry->load('telegram', [
 *             'className' => TelegramChannel::class,
 *         ]);
 *     }
 *
 *     public function getDefaultConfig(): array
 *     {
 *         return [
 *             'token' => env('TELEGRAM_BOT_TOKEN'),
 *             'timeout' => 30,
 *         ];
 *     }
 * }
 * ```
 */
interface ChannelProviderInterface
{
    /**
     * Get channel names provided by this provider
     *
     * Returns an array of channel names that this provider will register.
     * This allows the core system to discover what channels are available.
     *
     * @return array<string> List of channel names (e.g., ['telegram', 'slack'])
     */
    public function provides(): array;

    /**
     * Register channels with the registry
     *
     * Called during the channel discovery process to register the provider's
     * channels with the core ChannelRegistry. The provider should load its
     * channels using the registry's load() method.
     *
     * @param \Crustum\Notification\Registry\ChannelRegistry $registry The channel registry
     * @return void
     */
    public function register(ChannelRegistry $registry): void;

    /**
     * Get default configuration for the channel
     *
     * Returns the default configuration array that will be merged with
     * user-provided configuration. This typically includes API keys,
     * timeouts, and other service-specific settings.
     *
     * @return array<string, mixed> Default configuration array
     */
    public function getDefaultConfig(): array;
}
