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

use Hyperf\Command\Command;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

class McpCommand extends Command
{
    public function __construct(
        protected Server $server,
        protected ?string $name,
        protected string $description = 'Run the MCP server.',
    ) {
        parent::__construct();
    }

    public function handle()
    {
        return $this->server->run(
            new StdioTransport()
        );
    }
}
