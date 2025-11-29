<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\Mcp;

use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\SessionInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                SessionInterface::class => fn ($container) => new InMemorySessionStore(3600),
            ],
            'listeners' => [
                Listener\RegisterMcpServerListener::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The configuration file of MCP server.',
                    'source' => __DIR__ . '/../publish/mcp.php',
                    'destination' => BASE_PATH . '/config/autoload/mcp.php',
                ],
            ],
        ];
    }
}
