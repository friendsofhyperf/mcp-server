<?php

declare(strict_types=1);

/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\Mcp\Tests;

use FriendsOfHyperf\Mcp\ServerRegistry;
use Hyperf\Command\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server\Builder;
use Mcp\Server;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \FriendsOfHyperf\Mcp\ServerRegistry
 */
class ServerRegistryTest extends TestCase
{
    private ContainerInterface $container;
    private ConfigInterface $config;
    private DispatcherFactory $dispatcherFactory;
    private ServerRegistry $serverRegistry;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->dispatcherFactory = $this->createMock(DispatcherFactory::class);

        $this->serverRegistry = new ServerRegistry(
            $this->dispatcherFactory,
            $this->container,
            $this->config
        );
    }

    public function testRegisterWithEmptyServersConfig(): void
    {
        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('mcp.servers', [])
            ->willReturn([]);

        // Should not throw any exceptions
        $this->serverRegistry->register();
        $this->assertTrue(true); // Test passes if no exceptions are thrown
    }

    public function testRegisterWithDisabledServer(): void
    {
        $servers = [
            'test-server' => [
                'enabled' => false,
                'name' => 'Test Server',
            ],
        ];

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('mcp.servers', [])
            ->willReturn($servers);

        $this->serverRegistry->register();
        $this->assertTrue(true); // Test passes if no exceptions are thrown
    }

    public function testRegisterWithEnabledServer(): void
    {
        $servers = [
            'test-server' => [
                'enabled' => true,
                'name' => 'Test Server',
                'version' => '1.0.0',
                'description' => 'A test MCP server',
            ],
        ];

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('mcp.servers', [])
            ->willReturn($servers);

        // Mock the container to return false for has() calls
        $this->container
            ->expects($this->any())
            ->method('has')
            ->willReturn(false);

        $this->serverRegistry->register();
        $this->assertTrue(true); // Test passes if no exceptions are thrown
    }

    public function testBuildServerCapabilities(): void
    {
        $reflection = new \ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('buildServerCapabilities');
        $method->setAccessible(true);

        $capabilities = [
            'tools' => true,
            'resources' => true,
            'prompts' => true,
            'logging' => false,
            'completions' => true,
        ];

        $serverCapabilities = $method->invoke($this->serverRegistry, $capabilities);

        $this->assertInstanceOf(ServerCapabilities::class, $serverCapabilities);
        $this->assertTrue($serverCapabilities->tools);
        $this->assertTrue($serverCapabilities->resources);
        $this->assertTrue($serverCapabilities->prompts);
        $this->assertFalse($serverCapabilities->logging);
        $this->assertTrue($serverCapabilities->completions);
    }

    public function testBuildServerWithBasicOptions(): void
    {
        $reflection = new \ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('buildServer');
        $method->setAccessible(true);

        $options = [
            'name' => 'Test Server',
            'version' => '1.0.0',
            'description' => 'A test server',
            'protocol_version' => '2024-11-05',
            'pagination_limit' => 100,
            'instructions' => 'Test instructions',
            'capabilities' => [
                'tools' => true,
                'resources' => false,
                'prompts' => false,
            ],
        ];

        // Mock container to return false for all has() calls
        $this->container
            ->method('has')
            ->willReturn(false);

        $server = $method->invoke($this->serverRegistry, $options);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(ServerRegistry::class, $this->serverRegistry);
    }
}