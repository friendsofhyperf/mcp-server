# MCP Server

[![Latest Stable Version](https://img.shields.io/packagist/v/friendsofhyperf/mcp-server)](https://packagist.org/packages/friendsofhyperf/mcp-server)
[![Total Downloads](https://img.shields.io/packagist/dt/friendsofhyperf/mcp-server)](https://packagist.org/packages/friendsofhyperf/mcp-server)
[![License](https://img.shields.io/packagist/l/friendsofhyperf/mcp-server)](https://github.com/friendsofhyperf/mcp-server)

**MCP Server** 是一个基于 Hyperf 框架的 Model Context Protocol (MCP) 服务器实现，提供了完整的 MCP 协议支持，包括工具、资源和提示管理功能。

## 🚀 特性

- **完整的 MCP 协议支持** - 实现了 Model Context Protocol 规范
- **多传输方式** - 支持 STDIO 和 HTTP 两种传输协议
- **会话管理** - 基于 Redis 的分布式会话存储
- **注解驱动** - 支持通过注解自动发现和注册工具、资源和提示
- **灵活配置** - 丰富的配置选项，支持多服务器实例
- **Hyperf 集成** - 完全集成 Hyperf 框架的依赖注入和事件系统
- **命令行支持** - 内置命令行工具用于启动 MCP 服务器

## 📋 要求

- PHP >= 8.1
- Hyperf ~3.1.0
- Redis 扩展
- Composer

## 📦 安装

```bash
composer require friendsofhyperf/mcp-server
```

## ⚙️ 配置

### 发布配置文件

```bash
php bin/hyperf.php vendor:publish friendsofhyperf/mcp-server
```

配置示例：

```php
<?php
return [
    'servers' => [
        [
            'enabled' => true,
            'name' => 'My MCP Server',
            'version' => '1.0.0',
            'description' => 'A powerful MCP server implementation',
            'website_url' => 'https://example.com',
            'icons' => [
                [
                    'url' => 'https://example.com/icon.png',
                    'media_type' => 'image/png',
                    'width' => 64,
                    'height' => 64
                ]
            ],

            // 服务器能力配置
            'capabilities' => [
                'tools' => true,
                'resources' => true,
                'prompts' => true,
                'completions' => true,
            ],

            // 协议版本
            'protocol_version' => '2024-11-05',

            // 分页限制
            'pagination_limit' => 100,

            // 会话配置
            'session' => [
                'ttl' => 3600,
                'store' => \Mcp\Server\Session\SessionInterface::class,
                'factory' => \Mcp\Server\Session\SessionFactory::class,
            ],

            // 类发现配置
            'discovery' => [
                'base_path' => BASE_PATH,
                'scan_dirs' => ['app', 'src'],
                'exclude_dirs' => ['vendor', 'tests'],
                // 'cache' => \Psr\SimpleCache\CacheInterface::class,
            ],

            // 路由配置（HTTP 传输）
            'http' => [
                'path' => '/mcp',
                'options' => [
                    'middleware' => ['auth']
                ],
                // 'server' => 'http', // 指定服务器名称（可选）
            ],

            // 命令行配置（STDIO 传输）
            'stdio' => [
                'name' => 'mcp:server',
                'description' => 'Start MCP server via STDIO'
            ]
        ]
    ]
];
```

## 🔧 使用

### 1. 创建工具

使用 `#[McpTool]` 注解创建工具：

```php
<?php

declare(strict_types=1);

namespace App\Tool;

use Mcp\Capability\Attribute\McpTool;

#[McpTool(
    name: 'calculator.add',
    description: 'Add two numbers together'
)]
class CalculatorTool
{
    public function handle(array $params): array
    {
        $result = $params['a'] + $params['b'];
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => "The sum of {$params['a']} and {$params['b']} is {$result}"
                ]
            ]
        ];
    }
}
```

### 2. 创建资源

使用 `#[McpResource]` 注解创建资源：

```php
<?php

declare(strict_types=1);

namespace App\Resource;

use Mcp\Capability\Attribute\McpResource;

#[McpResource(
    uri: 'file:///var/log/app.log',
    name: 'Application Log',
    description: 'Current application log file',
    mimeType: 'text/plain'
)]
class LogResource
{
    public function handle(array $params): array
    {
        $content = file_get_contents('/var/log/app.log');
        return [
            'contents' => [
                [
                    'uri' => $params['uri'],
                    'mimeType' => 'text/plain',
                    'text' => $content
                ]
            ]
        ];
    }
}
```

### 3. 创建提示

使用 `#[McpPrompt]` 注解创建提示：

```php
<?php

declare(strict_types=1);

namespace App\Prompt;

use Mcp\Capability\Attribute\McpPrompt;

#[McpPrompt(
    name: 'code-review',
    description: 'Generate code review suggestions'
)]
class CodeReviewPrompt
{
    public function handle(array $params): array
    {
        $prompt = "Please review the following code and provide suggestions for improvement:\n\n";
        $prompt .= $params['code'] ?? '';

        return [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => $prompt
                    ]
                ]
            ]
        ];
    }
}
```

### 4. 启动服务器

```bash
# 启动 Hyperf 服务器，MCP 服务将自动注册并启动
php bin/hyperf.php start
```

MCP 服务器会自动：

- 注册 HTTP 路由端点（默认为 `/mcp`）
- 注册命令行工具（用于 STDIO 传输）
- 根据配置自动发现并注册工具、资源和提示

## 🏗️ 架构

### 核心组件

- **ServerManager** - 服务器管理器，负责创建和配置 MCP 服务器实例
- **RegisterMcpServerListener** - 应用启动监听器，自动注册配置的 MCP 服务器
- **ConfigProvider** - Hyperf 配置提供者，注册服务依赖

### 关键特性

1. **多服务器支持** - 支持在单个应用中运行多个 MCP 服务器实例
2. **灵活的传输层** - 支持 STDIO 和 HTTP 传输协议
3. **会话管理** - 支持内存和 Redis 会话存储
4. **自动发现** - 基于注解的自动组件发现和注册
5. **事件驱动** - 集成 Hyperf 事件系统

## 🔌 扩展

### 自定义会话存储

实现 `SessionInterface` 接口：

```php
<?php

use Mcp\Server\Session\SessionInterface;

class CustomSessionStore implements SessionInterface
{
    public function get(string $sessionId): ?array
    {
        // 自定义会话获取逻辑
    }

    public function set(string $sessionId, array $data, int $ttl = null): void
    {
        // 自定义会话存储逻辑
    }

    public function delete(string $sessionId): void
    {
        // 自定义会话删除逻辑
    }
}
```

### 自定义处理器

实现请求和通知处理器：

```php
<?php

use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Transport\TransportInterface;

class CustomRequestHandler implements RequestHandlerInterface
{
    public function canHandle(string $method): bool
    {
        return $method === 'custom/method';
    }

    public function handle(array $params, TransportInterface $transport): mixed
    {
        // 自定义请求处理逻辑
    }
}
```

## 🧪 测试

```bash
# 运行测试
composer test

# 代码分析
composer analyse src

# 代码格式化
composer cs-fix
```

## 📝 配置选项

| 配置项 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| `enabled` | bool | `true` | 是否启用服务器 |
| `name` | string | `'MCP Server'` | 服务器名称 |
| `version` | string | `'1.0.0'` | 服务器版本 |
| `description` | string | `'A MCP server.'` | 服务器描述 |
| `protocol_version` | string | `'2024-11-05'` | MCP 协议版本 |
| `pagination_limit` | int | `100` | 分页限制 |
| `logger` | string | - | 日志服务名称 |
| `capabilities` | array | - | 服务器能力配置 |
| `session.ttl` | int | `3600` | 会话 TTL（秒） |
| `discovery.scan_dirs` | array | `['.', 'src', 'app']` | 自动发现扫描目录 |
| `discovery.exclude_dirs` | array | `['vendor', 'tests']` | 排除的扫描目录 |
| `router.path` | string | `'/mcp'` | HTTP 路由路径 |
| `router.options` | array | `[]` | 路由中间件等选项 |
| `command.signature` | string | `'mcp:stdio'` | 命令行工具签名 |
| `command.description` | string | `'Run MCP stdio server.'` | 命令行工具描述 |

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

1. Fork 项目
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 打开 Pull Request

## 📄 许可证

本项目采用 MIT 许可证。详见 [LICENSE](LICENSE) 文件。

## 🔗 相关链接

- [Model Context Protocol 规范](https://modelcontextprotocol.io/)
- [Hyperf 框架](https://hyperf.io/)
- [MCP SDK](https://github.com/modelcontextprotocol/servers)

## 📞 支持

- 问题反馈: [GitHub Issues](https://github.com/hyperf/mcp-server/issues)
- 官方文档: [Hyperf Wiki](https://hyperf.wiki)
- 社区讨论: [Hyperf 官方群](https://hyperf.io/contact)
