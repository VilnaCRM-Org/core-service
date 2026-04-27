<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Collection\Stub;

use App\Shared\Application\DTO\CacheRefreshTarget;
use App\Shared\Application\Resolver\CacheRefreshTargetResolverInterface;

final readonly class CollectionTestTargetResolver implements CacheRefreshTargetResolverInterface
{
    public function supports(string $context, string $family): bool
    {
        return $context === 'customer' && $family === 'detail';
    }

    public function resolve(
        string $context,
        string $family,
        string $identifierName,
        string $identifierValue
    ): CacheRefreshTarget {
        return CacheRefreshTarget::create($context, $family, $identifierName, $identifierValue);
    }
}
