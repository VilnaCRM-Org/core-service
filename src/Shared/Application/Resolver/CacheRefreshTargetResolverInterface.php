<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver;

use App\Shared\Application\DTO\CacheRefreshTarget;

interface CacheRefreshTargetResolverInterface
{
    public function supports(string $context, string $family): bool;

    public function resolve(
        string $context,
        string $family,
        string $identifierName,
        string $identifierValue
    ): CacheRefreshTarget;
}
