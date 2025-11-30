<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\McpServer;

use Hyperf\Contract\ConfigInterface;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server;
use Mcp\Server\Builder;
use Mcp\Server\Handler\Notification\NotificationHandlerInterface;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Transport\TransportInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use WeakMap;

class ServerBuilder
{
    /** @var WeakMap<Server,TransportInterface> */
    protected WeakMap $transports;

    public function __construct(
        protected ContainerInterface $container,
        protected ConfigInterface $config
    ) {
        $this->transports = new WeakMap();
    }

    public function build(array $options)
    {
        return $this->buildServer($options);
    }

    protected function buildServer(array $options): Server
    {
        $builder = new Builder();

        // 服务器基本信息
        $builder->setServerInfo(
            name: $options['name'] ?? 'MCP Server',
            version: $options['version'] ?? '1.0.0',
            description: $options['description'] ?? 'A MCP server.',
            icons: $options['icons'] ?? null,
            websiteUrl: $options['website_url'] ?? null
        );

        // 设置容器
        $builder->setContainer($this->container);

        // 日志配置
        if (isset($options['logger']) && $this->container->has($options['logger'])) {
            $builder->setLogger($this->container->get($options['logger']));
        } elseif ($this->container->has(LoggerInterface::class)) {
            $builder->setLogger($this->container->get(LoggerInterface::class));
        }

        // 事件调度器
        if ($this->container->has(EventDispatcherInterface::class)) {
            $builder->setEventDispatcher($this->container->get(EventDispatcherInterface::class));
        }

        // 协议版本
        if (isset($options['protocol_version'])) {
            $builder->setProtocolVersion(ProtocolVersion::from($options['protocol_version']));
        }

        // 分页限制
        if (isset($options['pagination_limit'])) {
            $builder->setPaginationLimit($options['pagination_limit']);
        }

        // 服务器指令
        if (isset($options['instructions'])) {
            $builder->setInstructions($options['instructions']);
        }

        // 服务器能力
        if (isset($options['capabilities'])) {
            $builder->setCapabilities($this->buildServerCapabilities($options['capabilities']));
        }

        // 类发现配置
        $discoveryOptions = $options['discovery'] ?? [];
        $cache = null;
        if (isset($discoveryOptions['cache']) && $this->container->has($discoveryOptions['cache'])) {
            $cache = $this->container->get($discoveryOptions['cache']);
        }

        $builder->setDiscovery(
            $discoveryOptions['base_path'] ?? BASE_PATH,
            $discoveryOptions['scan_dirs'] ?? ['.', 'src', 'app'],
            $discoveryOptions['exclude_dirs'] ?? ['vendor', 'tests'],
            $cache
        );

        // 会话管理
        $this->configureSession($builder, $options['session'] ?? []);

        // 注册处理器
        $this->registerHandlers($builder, $options);

        // 手动注册工具、资源、提示
        $this->registerManualRegistrations($builder, $options);

        // 加载器
        if (isset($options['loaders'])) {
            $this->registerLoaders($builder, $options['loaders']);
        }

        return $builder->build();
    }

    protected function buildServerCapabilities(array $capabilities): ServerCapabilities
    {
        return new ServerCapabilities(
            tools: $capabilities['tools'] ?? false,
            toolsListChanged: $capabilities['tools_list_changed'] ?? false,
            resources: $capabilities['resources'] ?? false,
            resourcesSubscribe: $capabilities['resources_subscribe'] ?? false,
            resourcesListChanged: $capabilities['resources_list_changed'] ?? false,
            prompts: $capabilities['prompts'] ?? false,
            promptsListChanged: $capabilities['prompts_list_changed'] ?? false,
            logging: $capabilities['logging'] ?? false,
            completions: $capabilities['completions'] ?? true,
        );
    }

    protected function configureSession(Builder $builder, array $sessionOptions): void
    {
        if (empty($sessionOptions)) {
            return;
        }

        $sessionStore = null;
        $sessionFactory = null;
        $ttl = $sessionOptions['ttl'] ?? 3600;

        if (isset($sessionOptions['store']) && $this->container->has($sessionOptions['store'])) {
            $sessionStore = $this->container->get($sessionOptions['store']);
        }

        if (isset($sessionOptions['factory']) && $this->container->has($sessionOptions['factory'])) {
            $sessionFactory = $this->container->get($sessionOptions['factory']);
        }

        if ($sessionStore) {
            $builder->setSession($sessionStore, $sessionFactory, $ttl);
        }
    }

    protected function registerHandlers(Builder $builder, array $options): void
    {
        // 注册请求处理器
        if (isset($options['request_handlers'])) {
            foreach ($options['request_handlers'] as $handlerClass) {
                if ($this->container->has($handlerClass)) {
                    $handler = $this->container->get($handlerClass);
                    if ($handler instanceof RequestHandlerInterface) {
                        $builder->addRequestHandler($handler);
                    }
                }
            }
        }

        // 注册通知处理器
        if (isset($options['notification_handlers'])) {
            foreach ($options['notification_handlers'] as $handlerClass) {
                if ($this->container->has($handlerClass)) {
                    $handler = $this->container->get($handlerClass);
                    if ($handler instanceof NotificationHandlerInterface) {
                        $builder->addNotificationHandler($handler);
                    }
                }
            }
        }
    }

    protected function registerManualRegistrations(Builder $builder, array $options): void
    {
        // 手动注册工具
        if (isset($options['tools'])) {
            foreach ($options['tools'] as $tool) {
                $builder->addTool(
                    handler: $tool['handler'],
                    name: $tool['name'] ?? null,
                    description: $tool['description'] ?? null,
                    annotations: $tool['annotations'] ?? null,
                    inputSchema: $tool['input_schema'] ?? null,
                    icons: $tool['icons'] ?? null,
                    meta: $tool['meta'] ?? null
                );
            }
        }

        // 手动注册资源
        if (isset($options['resources'])) {
            foreach ($options['resources'] as $resource) {
                $builder->addResource(
                    handler: $resource['handler'],
                    uri: $resource['uri'],
                    name: $resource['name'] ?? null,
                    description: $resource['description'] ?? null,
                    mimeType: $resource['mime_type'] ?? null,
                    size: $resource['size'] ?? null,
                    annotations: $resource['annotations'] ?? null,
                    icons: $resource['icons'] ?? null,
                    meta: $resource['meta'] ?? null
                );
            }
        }

        // 手动注册资源模板
        if (isset($options['resource_templates'])) {
            foreach ($options['resource_templates'] as $template) {
                $builder->addResourceTemplate(
                    handler: $template['handler'],
                    uriTemplate: $template['uri_template'],
                    name: $template['name'] ?? null,
                    description: $template['description'] ?? null,
                    mimeType: $template['mime_type'] ?? null,
                    annotations: $template['annotations'] ?? null,
                    meta: $template['meta'] ?? null
                );
            }
        }

        // 手动注册提示
        if (isset($options['prompts'])) {
            foreach ($options['prompts'] as $prompt) {
                $builder->addPrompt(
                    handler: $prompt['handler'],
                    name: $prompt['name'] ?? null,
                    description: $prompt['description'] ?? null,
                    icons: $prompt['icons'] ?? null,
                    meta: $prompt['meta'] ?? null
                );
            }
        }
    }

    protected function registerLoaders(Builder $builder, array $loaders): void
    {
        foreach ($loaders as $loaderClass) {
            if ($this->container->has($loaderClass)) {
                $loader = $this->container->get($loaderClass);
                $builder->addLoaders($loader);
            }
        }
    }
}
