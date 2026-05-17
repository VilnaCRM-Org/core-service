<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Collection;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheInvalidationRule;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Infrastructure\Collection\CacheInvalidationRuleCollection;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandHandlerCollection;
use App\Shared\Infrastructure\Collection\CacheRefreshPolicyCollection;
use App\Shared\Infrastructure\Collection\CacheRefreshTargetResolverCollection;
use App\Tests\Unit\Shared\Infrastructure\Collection\Stub\CollectionTestRefreshHandler;
use App\Tests\Unit\Shared\Infrastructure\Collection\Stub\CollectionTestTargetResolver;
use App\Tests\Unit\UnitTestCase;

final class CacheRefreshCollectionTest extends UnitTestCase
{
    public function testRuleCollectionKeepsRules(): void
    {
        $rule = new CacheInvalidationRule(
            'customer',
            'odm_change_set',
            self::class,
            CacheInvalidationRule::OPERATION_UPDATED,
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );

        $collection = new CacheInvalidationRuleCollection($rule);

        self::assertCount(1, $collection);
        self::assertSame([$rule], iterator_to_array($collection));
    }

    public function testCommandCollectionWithReturnsNewCollection(): void
    {
        $first = $this->refreshCommand('first');
        $second = $this->refreshCommand('second');
        $collection = new CacheRefreshCommandCollection($first);
        $updated = $collection->with($second);

        self::assertCount(1, $collection);
        self::assertCount(2, $updated);
        self::assertSame([$first], iterator_to_array($collection));
        self::assertSame([$first, $second], iterator_to_array($updated));
    }

    public function testHandlerCollectionKeepsHandlers(): void
    {
        $handler = new CollectionTestRefreshHandler();
        $collection = new CacheRefreshCommandHandlerCollection($handler);

        self::assertCount(1, $collection);
        self::assertSame([$handler], iterator_to_array($collection));
    }

    public function testPolicyCollectionKeepsPolicies(): void
    {
        $policy = new CacheRefreshPolicy(
            'customer',
            'detail',
            600,
            1.0,
            'stale_while_revalidate',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );
        $collection = new CacheRefreshPolicyCollection($policy);

        self::assertCount(1, $collection);
        self::assertSame([$policy], iterator_to_array($collection));
    }

    public function testTargetResolverCollectionKeepsResolvers(): void
    {
        $resolver = new CollectionTestTargetResolver();
        $collection = new CacheRefreshTargetResolverCollection($resolver);

        self::assertCount(1, $collection);
        self::assertSame([$resolver], iterator_to_array($collection));
        self::assertTrue($resolver->supports('customer', 'detail'));
        self::assertSame('customer', $resolver->resolve(
            'customer',
            'detail',
            'id',
            '123'
        )->context());
    }

    private function refreshCommand(string $sourceId): CacheRefreshCommand
    {
        return new CacheRefreshCommand(
            'customer',
            'detail',
            'customer_id',
            (string) $this->faker->ulid(),
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH,
            'test',
            $sourceId,
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );
    }
}
