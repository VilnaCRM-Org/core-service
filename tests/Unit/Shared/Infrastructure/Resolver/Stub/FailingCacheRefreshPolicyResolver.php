<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver\Stub;

use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Exception\UnsupportedCacheRefreshPolicyException;
use App\Shared\Application\Resolver\CacheRefreshPolicyResolverInterface;

final class FailingCacheRefreshPolicyResolver implements CacheRefreshPolicyResolverInterface
{
    private int $calls = 0;

    public function resolve(string $context, string $family): CacheRefreshPolicy
    {
        ++$this->calls;

        throw UnsupportedCacheRefreshPolicyException::forContext($context);
    }

    public function calls(): int
    {
        return $this->calls;
    }
}
