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
        $rule = CacheInvalidationRule::create(
            'customer',
            'odm_change_set',
            self::class,
            CacheInvalidationRule::OPERATION_UPDATED,
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );

        $collection = CacheInvalidationRuleCollection::create($rule);

        self::assertCount(1, $collection);
        self::assertSame([$rule], iterator_to_array($collection));
    }

    public function testCommandCollectionWithReturnsNewCollection(): void
    {
        $first = $this->refreshCommand('first');
        $second = $this->refreshCommand('second');
        $collection = CacheRefreshCommandCollection::create($first);
        $updated = $collection->with($second);

        self::assertCount(1, $collection);
        self::assertCount(2, $updated);
        self::assertSame([$first], iterator_to_array($collection));
        self::assertSame([$first, $second], iterator_to_array($updated));
    }

    public function testHandlerCollectionCanBeBuiltFromIterable(): void
    {
        $handler = new CollectionTestRefreshHandler();
        $collection = CacheRefreshCommandHandlerCollection::fromIterable([$handler]);

        self::assertCount(1, $collection);
        self::assertSame([$handler], iterator_to_array($collection));
    }

    public function testPolicyCollectionKeepsPolicies(): void
    {
        $policy = CacheRefreshPolicy::create(
            'customer',
            'detail',
            600,
            1.0,
            'stale_while_revalidate',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );
        $collection = CacheRefreshPolicyCollection::create($policy);

        self::assertCount(1, $collection);
        self::assertSame([$policy], iterator_to_array($collection));
    }

    public function testTargetResolverCollectionCanBeBuiltFromIterable(): void
    {
        $resolver = new CollectionTestTargetResolver();
        $collection = CacheRefreshTargetResolverCollection::fromIterable([$resolver]);

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
        return CacheRefreshCommand::create(
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
