<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */
return [
    'servers' => [
        [
            'enabled' => true,

            // Server basic information
            'name' => 'Hyperf MCP Server',
            'version' => '1.0.0',
            'description' => 'A powerful MCP server built on Hyperf framework.',
            'website_url' => 'https://hyperf.io',

            // Server icons (optional)
            'icons' => [
                [
                    'url' => 'https://hyperf.io/favicon.ico',
                    'width' => 16,
                    'height' => 16,
                    'mediaType' => 'image/x-icon',
                ],
            ],

            // Server instructions for clients
            'instructions' => 'This is a Hyperf-based MCP server providing various tools and resources for AI assistants.',

            // Protocol version (optional)
            'protocol_version' => '2024-11-05',

            // Pagination limit for list operations
            'pagination_limit' => 50,

            // Logger service ID (optional, will use PSR-3 logger if available)
            'logger' => null, // e.g., 'hyperf.logger'

            // Session configuration
            'session' => [
                'ttl' => 3600, // 1 hour
                'store' => null, // SessionStore service ID
                'factory' => null, // SessionFactory service ID
            ],

            // Custom server capabilities (optional, will auto-detect if not set)
            // 'capabilities' => [
            //     'tools' => true,
            //     'tools_list_changed' => true,
            //     'resources' => true,
            //     'resources_subscribe' => false,
            //     'resources_list_changed' => true,
            //     'prompts' => true,
            //     'prompts_list_changed' => true,
            //     'logging' => false,
            //     'completions' => true,
            // ],

            // Class discovery configuration
            'discovery' => [
                'base_path' => BASE_PATH,
                'scan_dirs' => ['.', 'src', 'app'],
                'exclude_dirs' => ['vendor', 'tests', 'config'],
                'cache' => null, // CacheInterface service ID for discovery cache
            ],

            // Custom handlers
            'request_handlers' => [
                // Add custom request handler service IDs here
            ],

            'notification_handlers' => [
                // Add custom notification handler service IDs here
            ],

            // Manual tool registrations
            'tools' => [
                // [
                //     'handler' => 'App\\Mcp\\Tools\\CustomTool',
                //     'name' => 'custom_tool',
                //     'description' => 'A custom tool',
                //     'input_schema' => ['type' => 'object', 'properties' => []],
                // ]
            ],

            // Manual resource registrations
            'resources' => [
                // [
                //     'handler' => 'App\\Mcp\\Resources\\CustomResource',
                //     'uri' => 'custom://example',
                //     'name' => 'Custom Resource',
                //     'description' => 'A custom resource',
                //     'mime_type' => 'text/plain',
                // ]
            ],

            // Manual resource template registrations
            'resource_templates' => [
                // [
                //     'handler' => 'App\\Mcp\\Resources\\CustomTemplateResource',
                //     'uri_template' => 'custom://{param}',
                //     'name' => 'Custom Template',
                //     'description' => 'A custom resource template',
                //     'mime_type' => 'text/plain',
                // ]
            ],

            // Manual prompt registrations
            'prompts' => [
                // [
                //     'handler' => 'App\\Mcp\\Prompts\\CustomPrompt',
                //     'name' => 'custom_prompt',
                //     'description' => 'A custom prompt',
                // ]
            ],

            // Custom loaders
            'loaders' => [
                // Add custom loader service IDs here
            ],

            // HTTP mode configuration
            'http' => [
                'path' => '/mcp',
                'options' => [
                    'middleware' => [], // Add middleware if needed
                ],
                // 'server' => 'http', // Specify server if needed
            ],

            // STDIO mode configuration
            'stdio' => [
                'name' => 'mcp:server',
                'description' => 'Run the MCP server via STDIO transport.',
            ],
        ],
    ],
];
