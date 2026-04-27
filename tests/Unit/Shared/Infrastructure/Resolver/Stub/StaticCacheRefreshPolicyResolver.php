<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver\Stub;

use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Exception\UnsupportedCacheRefreshPolicyException;
use App\Shared\Application\Resolver\CacheRefreshPolicyResolverInterface;

final class StaticCacheRefreshPolicyResolver implements CacheRefreshPolicyResolverInterface
{
    private int $calls = 0;

    public function __construct(
        private readonly CacheRefreshPolicy $policy
    ) {
    }

    public function resolve(string $context, string $family): CacheRefreshPolicy
    {
        ++$this->calls;

        if ($this->policy->context() !== $context || $this->policy->family() !== $family) {
            throw UnsupportedCacheRefreshPolicyException::forContext($context);
        }

        return $this->policy;
    }

    public function calls(): int
    {
        return $this->calls;
    }
}
