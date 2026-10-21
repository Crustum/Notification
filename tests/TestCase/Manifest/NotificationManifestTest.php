<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Manifest;

use Cake\TestSuite\TestCase;
use Crustum\Notification\Manifest\NotificationManifest;
use Crustum\Notification\NotificationPlugin;
use Crustum\PluginManifest\Manifest\ManifestResolver;

/**
 * Covers the standalone manifest provider: identical operations to the
 * former plugin-class manifest, discoverable without the interface.
 */
class NotificationManifestTest extends TestCase
{
    /**
     * The provider emits migrations, config, bootstrap and star operations.
     *
     * @return void
     */
    public function testManifestOperations(): void
    {
        $ops = NotificationManifest::manifest();
        $tags = array_column($ops, 'tag');

        $this->assertContains('migrations', $tags);
        $this->assertContains('config', $tags);
        $this->assertContains('bootstrap', $tags);
        $this->assertContains('star-repo', $tags);
    }

    /**
     * The resolver finds the provider for the plugin class.
     *
     * @return void
     */
    public function testResolverFindsProvider(): void
    {
        $this->assertSame(
            NotificationManifest::class,
            ManifestResolver::providerClass(NotificationPlugin::class),
        );
        $this->assertSame(NotificationManifest::manifest(), ManifestResolver::manifestFor(NotificationPlugin::class));
    }

    /**
     * The plugin class itself carries no manifest contract.
     *
     * @return void
     */
    public function testPluginClassHasNoManifestContract(): void
    {
        $this->assertFalse(method_exists(NotificationPlugin::class, 'manifest'));
    }
}
