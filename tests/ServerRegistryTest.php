<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\Tests\McpServer;

use FriendsOfHyperf\McpServer\ServerRegistry;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * @covers \FriendsOfHyperf\McpServer\ServerRegistry
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
        $reflection = new ReflectionClass($this->serverRegistry);
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
        $reflection = new ReflectionClass($this->serverRegistry);
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

    public function testBuildServerCapabilitiesWithAllOptions(): void
    {
        $reflection = new ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('buildServerCapabilities');
        $method->setAccessible(true);

        $capabilities = [
            'tools' => true,
            'tools_list_changed' => true,
            'resources' => true,
            'resources_subscribe' => true,
            'resources_list_changed' => true,
            'prompts' => true,
            'prompts_list_changed' => true,
            'logging' => true,
            'completions' => false,
        ];

        $serverCapabilities = $method->invoke($this->serverRegistry, $capabilities);

        $this->assertInstanceOf(ServerCapabilities::class, $serverCapabilities);
        $this->assertTrue($serverCapabilities->tools);
        $this->assertTrue($serverCapabilities->toolsListChanged);
        $this->assertTrue($serverCapabilities->resources);
        $this->assertTrue($serverCapabilities->resourcesSubscribe);
        $this->assertTrue($serverCapabilities->resourcesListChanged);
        $this->assertTrue($serverCapabilities->prompts);
        $this->assertTrue($serverCapabilities->promptsListChanged);
        $this->assertTrue($serverCapabilities->logging);
        $this->assertFalse($serverCapabilities->completions);
    }

    public function testBuildServerCapabilitiesWithDefaultValues(): void
    {
        $reflection = new ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('buildServerCapabilities');
        $method->setAccessible(true);

        // Empty capabilities array should use defaults
        $capabilities = [];

        $serverCapabilities = $method->invoke($this->serverRegistry, $capabilities);

        $this->assertInstanceOf(ServerCapabilities::class, $serverCapabilities);
        // Default values from buildServerCapabilities method
        $this->assertFalse($serverCapabilities->tools);
        $this->assertFalse($serverCapabilities->toolsListChanged);
        $this->assertFalse($serverCapabilities->resources);
        $this->assertFalse($serverCapabilities->resourcesSubscribe);
        $this->assertFalse($serverCapabilities->resourcesListChanged);
        $this->assertFalse($serverCapabilities->prompts);
        $this->assertFalse($serverCapabilities->promptsListChanged);
        $this->assertFalse($serverCapabilities->logging);
        $this->assertTrue($serverCapabilities->completions); // Default is true
    }

    public function testBuildServerWithMinimalOptions(): void
    {
        $reflection = new ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('buildServer');
        $method->setAccessible(true);

        // Minimal options to test default values
        $options = [];

        // Mock container to return false for all has() calls
        $this->container
            ->method('has')
            ->willReturn(false);

        $server = $method->invoke($this->serverRegistry, $options);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testBuildServerWithWebsiteUrl(): void
    {
        $reflection = new ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('buildServer');
        $method->setAccessible(true);

        $options = [
            'name' => 'Test Server',
            'version' => '1.0.0',
            'description' => 'A test server',
            'website_url' => 'https://example.com',
        ];

        $this->container
            ->method('has')
            ->willReturn(false);

        $server = $method->invoke($this->serverRegistry, $options);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testBuildServerWithIcons(): void
    {
        $reflection = new ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('buildServer');
        $method->setAccessible(true);

        $options = [
            'name' => 'Test Server',
            'version' => '1.0.0',
            'icons' => [
                [
                    'url' => 'https://example.com/icon.png',
                    'width' => 32,
                    'height' => 32,
                    'mediaType' => 'image/png',
                ],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturn(false);

        $server = $method->invoke($this->serverRegistry, $options);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testConfigureSessionWithEmptyOptions(): void
    {
        $reflection = new ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('configureSession');
        $method->setAccessible(true);

        // Get a Builder instance through buildServer reflection
        $buildServerMethod = $reflection->getMethod('buildServer');
        $buildServerMethod->setAccessible(true);

        $this->container
            ->method('has')
            ->willReturn(false);

        // This should not throw any exceptions
        $server = $buildServerMethod->invoke($this->serverRegistry, [
            'session' => [],
        ]);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testRegisterWithMultipleServers(): void
    {
        $servers = [
            [
                'enabled' => true,
                'name' => 'Server 1',
                'version' => '1.0.0',
            ],
            [
                'enabled' => true,
                'name' => 'Server 2',
                'version' => '2.0.0',
            ],
            [
                'enabled' => false,
                'name' => 'Server 3',
                'version' => '3.0.0',
            ],
        ];

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('mcp.servers', [])
            ->willReturn($servers);

        $this->container
            ->method('has')
            ->willReturn(false);

        $this->serverRegistry->register();
        $this->assertTrue(true);
    }

    public function testBuildServerWithDiscoveryOptions(): void
    {
        $reflection = new ReflectionClass($this->serverRegistry);
        $method = $reflection->getMethod('buildServer');
        $method->setAccessible(true);

        $options = [
            'name' => 'Test Server',
            'discovery' => [
                'base_path' => '/custom/path',
                'scan_dirs' => ['app', 'src'],
                'exclude_dirs' => ['vendor', 'tests', 'node_modules'],
            ],
        ];

        $this->container
            ->method('has')
            ->willReturn(false);

        $server = $method->invoke($this->serverRegistry, $options);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testRegisterWithServerWithoutEnabledKey(): void
    {
        // When 'enabled' key is missing, it defaults to true
        $servers = [
            [
                'name' => 'Server Without Enabled',
                'version' => '1.0.0',
            ],
        ];

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with('mcp.servers', [])
            ->willReturn($servers);

        $this->container
            ->method('has')
            ->willReturn(false);

        $this->serverRegistry->register();
        $this->assertTrue(true);
    }
}
