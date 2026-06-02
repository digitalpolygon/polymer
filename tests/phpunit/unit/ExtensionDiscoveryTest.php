<?php

namespace DigitalPolygon\PolymerTest\phpunit\unit;

use Composer\Autoload\ClassLoader;
use DigitalPolygon\Polymer\Core\Robo\Discovery\ExtensionDiscovery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Covers enable/disable gating in ExtensionDiscovery.
 *
 * A fixture .polymer directory is built on disk with two installed plugins
 * (foo, bar) so we can assert that only enabled AND installed plugins are
 * surfaced for namespace/command/hook registration.
 */
class ExtensionDiscoveryTest extends TestCase
{
    protected string $repoRoot;
    protected string $polymerRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoRoot = sys_get_temp_dir() . '/polymer-ext-' . uniqid();
        $this->polymerRoot = $this->repoRoot . '/.polymer';

        // Plugins installed both as symlinks (path repositories) and as a real
        // directory (dist/committed), so discovery is covered for both layouts.
        $this->installPlugin('foo');
        $this->installPlugin('bar');
        $this->installPlugin('baz', false);

        // Enable foo plus a "ghost" that is referenced but not installed.
        $this->writeConfig(['foo', 'ghost']);
    }

    protected function tearDown(): void
    {
        $this->deleteRecursive($this->repoRoot);
        parent::tearDown();
    }

    protected function discovery(): ExtensionDiscovery
    {
        return new ExtensionDiscovery(new ClassLoader(), $this->repoRoot, $this->polymerRoot);
    }

    protected function installPlugin(string $name, bool $asSymlink = true): void
    {
        if (!$asSymlink) {
            // Real directory under plugins/custom (e.g. dist install or a
            // committed plugin), not a symlink.
            $dir = $this->polymerRoot . '/plugins/custom/' . $name;
            mkdir($dir . '/src', 0777, true);
            file_put_contents($dir . '/' . $name . '.poly_info.yml', "name: $name\n");
            return;
        }

        // Mirror the path-repository layout: the plugin source lives elsewhere
        // and is symlinked under .polymer/plugins/contrib.
        $source = $this->repoRoot . '/sources/' . $name;
        mkdir($source . '/src', 0777, true);
        file_put_contents($source . '/' . $name . '.poly_info.yml', "name: $name\n");

        $contrib = $this->polymerRoot . '/plugins/contrib';
        if (!is_dir($contrib)) {
            mkdir($contrib, 0777, true);
        }
        symlink($source, $contrib . '/' . $name);
    }

    /**
     * @param array<int, string> $enabled
     */
    protected function writeConfig(array $enabled): void
    {
        file_put_contents($this->polymerRoot . '/config.yml', Yaml::dump(['enabled_extensions' => $enabled], 4, 2));
    }

    protected function deleteRecursive(string $path): void
    {
        // Unlink symlinks directly rather than descending into their targets.
        if (is_link($path)) {
            unlink($path);
            return;
        }
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path)) {
            unlink($path);
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->deleteRecursive($path . '/' . $entry);
        }
        rmdir($path);
    }

    public function testInstalledExtensionsReflectDiskRegardlessOfEnabledState(): void
    {
        $installed = $this->discovery()->getInstalledExtensions();
        // Symlinked (foo, bar) and real-directory (baz) plugins are all found.
        $this->assertEqualsCanonicalizing(['foo', 'bar', 'baz'], array_keys($installed));
    }

    public function testRealDirectoryPluginCanBeEnabledAndRegistered(): void
    {
        $discovery = $this->discovery();
        $this->assertArrayHasKey('baz', $discovery->getInstalledExtensions());
        // Installed but disabled: not registerable yet.
        $this->assertArrayNotHasKey('baz', $discovery->getExtensionNamespaceInfo());

        $discovery->enableExtension('baz');
        $this->assertArrayHasKey('baz', $discovery->getExtensionNamespaceInfo());
    }

    public function testEnabledNamesReflectRawConfig(): void
    {
        // Includes the not-installed "ghost" since this is the raw config list.
        $this->assertEqualsCanonicalizing(['foo', 'ghost'], $this->discovery()->getEnabledExtensionNames());
    }

    public function testOnlyEnabledInstalledExtensionsAreRegisterable(): void
    {
        // getExtensionNamespaceInfo() is the basis for namespace, command, and
        // hook registration. Disabled (bar) and enabled-but-missing (ghost)
        // must both be absent.
        $namespaces = $this->discovery()->getExtensionNamespaceInfo();
        $this->assertSame(['foo'], array_keys($namespaces));
        $this->assertSame('DigitalPolygon\\Polymer\\foo', $namespaces['foo']['namespace']);
    }

    public function testIsExtensionEnabled(): void
    {
        $discovery = $this->discovery();
        $this->assertTrue($discovery->isExtensionEnabled('foo'));
        $this->assertFalse($discovery->isExtensionEnabled('bar'));
    }

    public function testEnableExtensionIsIdempotentAndPersists(): void
    {
        $discovery = $this->discovery();

        $this->assertTrue($discovery->enableExtension('bar'), 'Enabling a disabled plugin reports a change.');
        $this->assertFalse($discovery->enableExtension('bar'), 'Enabling an already-enabled plugin reports no change.');

        // Persisted to config.yml and now registerable.
        $config = Yaml::parseFile($this->polymerRoot . '/config.yml');
        $this->assertContains('bar', $config['enabled_extensions']);
        $this->assertEqualsCanonicalizing(['foo', 'bar'], array_keys($discovery->getExtensionNamespaceInfo()));
    }

    public function testDisableExtensionRemovesItFromRegistration(): void
    {
        $discovery = $this->discovery();

        $this->assertTrue($discovery->disableExtension('foo'));
        $this->assertFalse($discovery->disableExtension('foo'), 'Disabling a non-enabled plugin reports no change.');

        $this->assertFalse($discovery->isExtensionEnabled('foo'));
        $this->assertSame([], array_keys($discovery->getExtensionNamespaceInfo()));
    }
}
