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
use Mcp\Server\Session\InMemorySessionStore;
use PHPUnit\Framework\TestCase;

/**
 * @covers \FriendsOfHyperf\McpServer\ConfigProvider
 */
class ConfigProviderTest extends TestCase
{
    public function testInvokeReturnsCorrectStructure(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('listeners', $config);
        $this->assertArrayHasKey('publish', $config);
    }

    public function testDependenciesConfiguration(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        $this->assertArrayHasKey('Mcp\Server\Session\SessionInterface', $config['dependencies']);
        $this->assertIsCallable($config['dependencies']['Mcp\Server\Session\SessionInterface']);
    }

    public function testListenersConfiguration(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        $this->assertIsArray($config['listeners']);
        $this->assertContains(\FriendsOfHyperf\Mcp\Listener\RegisterMcpServerListener::class, $config['listeners']);
    }

    public function testPublishConfiguration(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        $this->assertIsArray($config['publish']);
        $this->assertCount(1, $config['publish']);

        $publishConfig = $config['publish'][0];
        $this->assertEquals('config', $publishConfig['id']);
        $this->assertEquals('The configuration file of MCP server.', $publishConfig['description']);
        $this->assertStringContainsString('publish/mcp.php', $publishConfig['source']);
        $this->assertStringContainsString('config/autoload/mcp.php', $publishConfig['destination']);
    }

    public function testSessionInterfaceDependency(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        $sessionFactory = $config['dependencies']['Mcp\Server\Session\SessionInterface'];
        $session = $sessionFactory([]);

        $this->assertInstanceOf(InMemorySessionStore::class, $session);
    }
}
