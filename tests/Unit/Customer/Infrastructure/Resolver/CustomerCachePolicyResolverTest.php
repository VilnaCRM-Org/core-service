<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Resolver;

use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCachePolicyResolver;
use App\Tests\Unit\UnitTestCase;

final class CustomerCachePolicyResolverTest extends UnitTestCase
{
    private const DETAIL_TTL = 1234;

    public function testResolveReturnsSharedPolicyDtoForCustomerContext(): void
    {
        $policy = $this->resolver()->resolve(
            CustomerCachePolicyCollection::CONTEXT,
            CustomerCachePolicyCollection::FAMILY_DETAIL
        );

        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $policy->context());
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $policy->family());
        self::assertSame(self::DETAIL_TTL, $policy->ttlSeconds());
        self::assertSame(1.0, $policy->beta());
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            $policy->refreshSource()
        );
    }

    public function testResolveRejectsUnsupportedContext(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cache context "invoice".');

        $this->resolver()->resolve(
            'invoice',
            CustomerCachePolicyCollection::FAMILY_DETAIL
        );
    }

    private function resolver(): CustomerCachePolicyResolver
    {
        return new CustomerCachePolicyResolver(new CustomerCachePolicyCollection(
            detailTtl: self::DETAIL_TTL
        ));
    }
}
