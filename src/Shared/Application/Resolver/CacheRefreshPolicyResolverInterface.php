<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver;

use App\Shared\Application\DTO\CacheRefreshPolicy;

interface CacheRefreshPolicyResolverInterface
{
    public function resolve(string $context, string $family): CacheRefreshPolicy;
}
