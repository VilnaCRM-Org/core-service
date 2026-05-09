<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver;

use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Resolver\CacheRefreshPolicyResolverInterface;
use App\Shared\Infrastructure\Resolver\CacheRefreshPolicyResolver;
use App\Tests\Unit\Shared\Infrastructure\Resolver\Stub\FailingCacheRefreshPolicyResolver;
use App\Tests\Unit\Shared\Infrastructure\Resolver\Stub\SelfReferencingPolicyResolverIterable;
use App\Tests\Unit\Shared\Infrastructure\Resolver\Stub\StaticCacheRefreshPolicyResolver;
use App\Tests\Unit\UnitTestCase;

final class CacheRefreshPolicyResolverTest extends UnitTestCase
{
    public function testResolvesFirstResolverThatSupportsContextAndFamily(): void
    {
        $policy = CacheRefreshPolicy::create(
            'customer',
            'detail',
            600,
            1.0,
            'stale_while_revalidate',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );
        $failingResolver = new FailingCacheRefreshPolicyResolver();
        $matchingResolver = new StaticCacheRefreshPolicyResolver($policy);
        $resolver = new CacheRefreshPolicyResolver([
            $failingResolver,
            $matchingResolver,
        ]);

        self::assertSame($policy, $resolver->resolve('customer', 'detail'));
        self::assertSame(1, $failingResolver->calls());
        self::assertSame(1, $matchingResolver->calls());
    }

    public function testSkipsItselfWhenTaggedIteratorContainsOuterResolver(): void
    {
        $policy = CacheRefreshPolicy::create(
            'customer',
            'lookup',
            300,
            null,
            'eventual',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );
        $matchingResolver = new StaticCacheRefreshPolicyResolver($policy);
        $iterator = new SelfReferencingPolicyResolverIterable($matchingResolver);
        $resolver = new CacheRefreshPolicyResolver($iterator);
        $iterator->setOuterResolver($resolver);

        self::assertSame($policy, $resolver->resolve('customer', 'lookup'));
    }

    public function testThrowsWhenNoRegisteredResolverSupportsPolicy(): void
    {
        $resolver = new CacheRefreshPolicyResolver([
            new FailingCacheRefreshPolicyResolver(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No cache refresh policy registered for "customer.detail".');

        $resolver->resolve('customer', 'detail');
    }

    public function testPropagatesResolverRuntimeFailures(): void
    {
        $resolver = new CacheRefreshPolicyResolver([
            new class() implements CacheRefreshPolicyResolverInterface {
                public function resolve(string $context, string $family): CacheRefreshPolicy
                {
                    throw new \RuntimeException('resolver backend failed');
                }
            },
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('resolver backend failed');

        $resolver->resolve('customer', 'detail');
    }
}
