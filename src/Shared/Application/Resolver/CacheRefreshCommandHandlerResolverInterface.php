<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver;

use App\Shared\Application\CommandHandler\CacheRefreshCommandHandlerBase;

interface CacheRefreshCommandHandlerResolverInterface
{
    public function resolve(string $context): CacheRefreshCommandHandlerBase;
}
