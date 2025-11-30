<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\McpServer\Contract;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

class_alias(PsrCacheInterface::class, CacheInterface::class);

if (! interface_exists(CacheInterface::class)) {
    interface CacheInterface extends PsrCacheInterface
    {
    }
}
