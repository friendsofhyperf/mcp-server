<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\McpServer\Tests;

use FriendsOfHyperf\McpServer\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the MCP package.
 */
class IntegrationTest extends TestCase
{
    public function testConfigProviderIntegration(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        // Test the complete configuration structure
        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('listeners', $config);
        $this->assertArrayHasKey('publish', $config);

        // Test dependencies
        $this->assertArrayHasKey('Mcp\Server\Session\SessionInterface', $config['dependencies']);
        $this->assertIsCallable($config['dependencies']['Mcp\Server\Session\SessionInterface']);

        // Test listeners
        $this->assertContains(\FriendsOfHyperf\Mcp\Listener\RegisterMcpServerListener::class, $config['listeners']);

        // Test publish configuration
        $this->assertIsArray($config['publish']);
        $this->assertNotEmpty($config['publish']);
    }

    public function testPackageStructure(): void
    {
        // Verify key files exist
        $this->assertFileExists(BASE_PATH . '/src/ConfigProvider.php');
        $this->assertFileExists(BASE_PATH . '/src/ServerRegistry.php');
        $this->assertFileExists(BASE_PATH . '/src/Listener/RegisterMcpServerListener.php');
        $this->assertFileExists(BASE_PATH . '/composer.json');
        $this->assertFileExists(BASE_PATH . '/phpunit.xml');
        $this->assertFileExists(BASE_PATH . '/tests/bootstrap.php');
    }

    public function testComposerJsonIsValid(): void
    {
        $composerJsonPath = BASE_PATH . '/composer.json';
        $this->assertFileExists($composerJsonPath);

        $content = file_get_contents($composerJsonPath);
        $this->assertJson($content);

        $composerData = json_decode($content, true);
        $this->assertIsArray($composerData);

        // Test required fields
        $this->assertArrayHasKey('name', $composerData);
        $this->assertArrayHasKey('description', $composerData);
        $this->assertArrayHasKey('license', $composerData);
        $this->assertArrayHasKey('keywords', $composerData);
        $this->assertArrayHasKey('autoload', $composerData);

        // Test keywords contain expected values
        $keywords = $composerData['keywords'];
        $this->assertIsArray($keywords);
        $this->assertContains('mcp', $keywords);
        $this->assertContains('ai', $keywords);
        $this->assertContains('claude', $keywords);
        $this->assertContains('php-8.1', $keywords);
        $this->assertContains('php-8.2', $keywords);
        $this->assertContains('php-8.3', $keywords);
        $this->assertContains('php-8.4', $keywords);
    }

    public function testTestClassAutoloading(): void
    {
        // Test that our test classes can be autoloaded
        $this->assertTrue(class_exists(ConfigProvider::class));
        $this->assertTrue(class_exists(\FriendsOfHyperf\Mcp\ServerRegistry::class));
        $this->assertTrue(class_exists(\FriendsOfHyperf\Mcp\Listener\RegisterMcpServerListener::class));
    }
}
