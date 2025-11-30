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

use FriendsOfHyperf\McpServer\McpCommand;
use FriendsOfHyperf\McpServer\ServerBuilder;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Router;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Container\ContainerInterface;

class RegisterMcpServerListener implements ListenerInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected ConfigInterface $config,
    ) {
        $this->container->get(DispatcherFactory::class); // !!! Don't remove this line
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        $servers = $this->config->get('mcp.servers', []);
        $builder = $this->container->get(ServerBuilder::class);

        foreach ($servers as $options) {
            if (! empty($options['http'])) {
                $this->registerRoute($builder, $options);
            }

            if (! empty($options['stdio'])) {
                $this->registerCommand($builder, $options);
            }
        }
    }

    protected function registerRoute(ServerBuilder $builder, array $options = []): void
    {
        $route = $options['http'] ?? [];
        $callable = fn () => Router::addRoute(
            ['GET', 'POST', 'OPTIONS', 'DELETE'],
            $route['path'] ?? '/mcp',
            function (RequestInterface $request) use ($builder, $options) {
                return $builder->build($options)
                    ->run(new StreamableHttpTransport($request));
            },
            $route['options'] ?? []
        );
        if (! empty($route['server'] ?? '')) {
            Router::addServer($route['server'], $callable);
        } else {
            $callable();
        }
    }

    protected function registerCommand(ServerBuilder $builder, array $options = []): void
    {
        $server = $builder->build($options);
        $command = new McpCommand(
            $server,
            $options['stdio']['name'] ?? 'mcp:server',
            $options['stdio']['description'] ?? 'Run the MCP server.'
        );
        $commandId = 'mcp.command.' . spl_object_hash($command);

        /** @var \Hyperf\Di\Container $container */
        $container = $this->container;
        $container->set($commandId, $command);

        $commands = $this->config->get('commands', []);
        $commands[] = $commandId;
        $this->config->set('commands', $commands);
    }
}
