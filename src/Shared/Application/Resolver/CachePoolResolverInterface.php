<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver;

use Symfony\Contracts\Cache\TagAwareCacheInterface;

interface CachePoolResolverInterface
{
    public function resolve(string $context): TagAwareCacheInterface;
}
