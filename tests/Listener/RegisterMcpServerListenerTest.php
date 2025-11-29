<?php

declare(strict_types=1);

/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\Mcp\Tests\Listener;

use FriendsOfHyperf\Mcp\Listener\RegisterMcpServerListener;
use FriendsOfHyperf\Mcp\ServerRegistry;
use Hyperf\Framework\Event\BootApplication;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \FriendsOfHyperf\Mcp\Listener\RegisterMcpServerListener
 */
class RegisterMcpServerListenerTest extends TestCase
{
    private ContainerInterface $container;
    private ServerRegistry $serverRegistry;
    private RegisterMcpServerListener $listener;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->serverRegistry = $this->createMock(ServerRegistry::class);
        $this->listener = new RegisterMcpServerListener($this->container);
    }

    public function testListenReturnsCorrectEvents(): void
    {
        $events = $this->listener->listen();

        $this->assertIsArray($events);
        $this->assertCount(1, $events);
        $this->assertEquals(BootApplication::class, $events[0]);
    }

    public function testProcessCallsRegisterOnServerRegistry(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(ServerRegistry::class)
            ->willReturn($this->serverRegistry);

        $this->serverRegistry
            ->expects($this->once())
            ->method('register');

        $event = new BootApplication();
        $this->listener->process($event);
    }

    public function testConstructWithContainer(): void
    {
        $this->assertInstanceOf(RegisterMcpServerListener::class, $this->listener);
    }
}