<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Collection;

use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;

final class CustomerCachePolicyCollectionTest extends UnitTestCase
{
    public function testDefinesDetailPolicy(): void
    {
        $policies = new CustomerCachePolicyCollection();

        self::assertSame([
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_DETAIL,
            'ttl' => 600,
            'beta' => 1.0,
            'consistency' => 'stale_while_revalidate',
            'refresh_source' => CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            'tags' => ['customer'],
        ], $policies->detail());
    }

    public function testDefinesLookupPolicy(): void
    {
        $policies = new CustomerCachePolicyCollection();

        self::assertSame([
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'ttl' => 300,
            'beta' => null,
            'consistency' => 'eventual',
            'refresh_source' => CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            'tags' => ['customer', 'customer.email'],
        ], $policies->lookup());
    }

    public function testDefinesCollectionPolicy(): void
    {
        $policies = new CustomerCachePolicyCollection();

        self::assertSame([
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_COLLECTION,
            'ttl' => 300,
            'beta' => null,
            'consistency' => 'invalidate_only',
            'refresh_source' => CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            'tags' => ['customer.collection'],
        ], $policies->forFamily(CustomerCachePolicyCollection::FAMILY_COLLECTION));
    }

    public function testDefinesReferencePolicy(): void
    {
        $policies = new CustomerCachePolicyCollection();

        self::assertSame([
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_REFERENCE,
            'ttl' => 1800,
            'beta' => null,
            'consistency' => 'invalidate_only',
            'refresh_source' => CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            'tags' => ['customer.reference'],
        ], $policies->forFamily(CustomerCachePolicyCollection::FAMILY_REFERENCE));
    }

    public function testDefinesNegativeLookupPolicy(): void
    {
        $policies = new CustomerCachePolicyCollection();

        self::assertSame([
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_NEGATIVE_LOOKUP,
            'ttl' => 60,
            'beta' => null,
            'consistency' => 'eventual',
            'refresh_source' => CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            'tags' => ['customer', 'customer.email'],
        ], $policies->forFamily(CustomerCachePolicyCollection::FAMILY_NEGATIVE_LOOKUP));
    }

    public function testUsesConfiguredTtls(): void
    {
        $policies = new CustomerCachePolicyCollection(
            detailTtl: 601,
            lookupTtl: 301,
            collectionTtl: 302,
            referenceTtl: 1801,
            negativeLookupTtl: 61
        );

        self::assertSame(601, $policies->detail()['ttl']);
        self::assertSame(301, $policies->lookup()['ttl']);
        self::assertSame(
            302,
            $policies->forFamily(CustomerCachePolicyCollection::FAMILY_COLLECTION)['ttl']
        );
        self::assertSame(
            1801,
            $policies->forFamily(CustomerCachePolicyCollection::FAMILY_REFERENCE)['ttl']
        );
        self::assertSame(
            61,
            $policies->forFamily(CustomerCachePolicyCollection::FAMILY_NEGATIVE_LOOKUP)['ttl']
        );
    }

    public function testResolvesKnownFamilies(): void
    {
        $policies = new CustomerCachePolicyCollection();

        self::assertSame(
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            $policies->forFamily(CustomerCachePolicyCollection::FAMILY_DETAIL)['family']
        );
        self::assertSame(
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            $policies->forFamily(CustomerCachePolicyCollection::FAMILY_LOOKUP)['family']
        );
        self::assertSame(
            CustomerCachePolicyCollection::FAMILY_COLLECTION,
            $policies->forFamily(CustomerCachePolicyCollection::FAMILY_COLLECTION)['family']
        );
        self::assertSame(
            CustomerCachePolicyCollection::FAMILY_REFERENCE,
            $policies->forFamily(CustomerCachePolicyCollection::FAMILY_REFERENCE)['family']
        );
        self::assertSame(
            CustomerCachePolicyCollection::FAMILY_NEGATIVE_LOOKUP,
            $policies->forFamily(CustomerCachePolicyCollection::FAMILY_NEGATIVE_LOOKUP)['family']
        );
    }

    public function testRejectsUnknownFamily(): void
    {
        $policies = new CustomerCachePolicyCollection();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported customer cache family "unknown".');

        $policies->forFamily('unknown');
    }

    public function testAccessorsReturnPolicyValuesAndMergeTags(): void
    {
        $policies = new CustomerCachePolicyCollection();
        $policy = $policies->lookup();

        self::assertSame(300, $policies->ttl($policy));
        self::assertSame(0.0, $policies->beta($policy));
        self::assertSame([
            'customer',
            'customer.email',
            'customer.email.hash',
        ], $policies->tags($policy, 'customer.email.hash', 'customer'));
    }
}
