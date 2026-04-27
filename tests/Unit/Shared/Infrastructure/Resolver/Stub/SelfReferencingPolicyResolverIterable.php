<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver\Stub;

use App\Shared\Application\Resolver\CacheRefreshPolicyResolverInterface;
use App\Shared\Infrastructure\Resolver\CacheRefreshPolicyResolver;

/**
 * @implements \IteratorAggregate<int, CacheRefreshPolicyResolverInterface>
 */
final class SelfReferencingPolicyResolverIterable implements \IteratorAggregate
{
    private ?CacheRefreshPolicyResolver $outerResolver = null;

    public function __construct(
        private readonly CacheRefreshPolicyResolverInterface $matchingResolver
    ) {
    }

    public function setOuterResolver(CacheRefreshPolicyResolver $outerResolver): void
    {
        $this->outerResolver = $outerResolver;
    }

    public function getIterator(): \Traversable
    {
        if ($this->outerResolver instanceof CacheRefreshPolicyResolver) {
            yield $this->outerResolver;
        }

        yield $this->matchingResolver;
    }
}
