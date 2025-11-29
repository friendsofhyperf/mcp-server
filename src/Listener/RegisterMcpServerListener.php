<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\McpServer\Listener;

use FriendsOfHyperf\McpServer\ServerRegistry;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Psr\Container\ContainerInterface;

class RegisterMcpServerListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        $manager = $this->container->get(ServerRegistry::class);
        $manager->register();
    }
}
