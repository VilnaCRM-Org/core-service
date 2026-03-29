<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Resolver;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheTagResolver;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;

final class CustomerCacheTagResolverTest extends UnitTestCase
{
    public function testResolveForDeletedCustomerWithCustomerBuildsTypedCollection(): void
    {
        $cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $resolver = new CustomerCacheTagResolver($cacheKeyBuilder);
        $customer = $this->createConfiguredMock(Customer::class, [
            'getUlid' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            'getEmail' => 'customer@example.com',
        ]);

        $cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with('customer@example.com')
            ->willReturn('hashed-email');

        self::assertSame(
            [
                'customer.collection',
                'customer.01ARZ3NDEKTSV4RRFFQ69G5FAV',
                'customer.email.hashed-email',
            ],
            iterator_to_array($resolver->resolveForDeletedCustomer($customer))
        );
    }

    public function testResolveForDeletedCustomerWithNullCustomerBuildsTagsFromParameters(): void
    {
        $cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $resolver = new CustomerCacheTagResolver($cacheKeyBuilder);

        $cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with('customer@example.com')
            ->willReturn('duplicate-hash');

        self::assertSame(
            [
                'customer.collection',
                'customer.duplicate-id',
                'customer.email.duplicate-hash',
            ],
            iterator_to_array(
                $resolver->resolveForDeletedCustomer(
                    customer: null,
                    deletedEmail: 'customer@example.com',
                    deletedId: 'duplicate-id'
                )
            )
        );
    }

    public function testResolveForDeletedCustomerCollectionIsCountable(): void
    {
        $cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $resolver = new CustomerCacheTagResolver($cacheKeyBuilder);

        $cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with('customer@example.com')
            ->willReturn('hashed-email');

        self::assertCount(
            3,
            $resolver->resolveForDeletedCustomer(
                customer: null,
                deletedEmail: 'customer@example.com',
                deletedId: 'customer-id'
            )
        );
    }
}
