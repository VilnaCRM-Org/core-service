<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Cache;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use PHPUnit\Framework\TestCase;

final class CacheKeyBuilderTest extends TestCase
{
    private CacheKeyBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new CacheKeyBuilder();
    }

    public function testBuildCustomerKey(): void
    {
        self::assertSame('customer.abc', $this->builder->buildCustomerKey('abc'));
    }

    public function testBuildCustomerEmailKeyHashesLowercasedEmail(): void
    {
        $key = $this->builder->buildCustomerEmailKey('John@Example.COM');
        $expectedHash = hash('sha256', 'john@example.com');

        self::assertSame('customer.email.' . $expectedHash, $key);
    }

    public function testBuildCustomerCollectionKeySortsFilters(): void
    {
        $key = $this->builder->buildCustomerCollectionKey(['b' => 2, 'a' => 1]);
        $expected = hash('sha256', json_encode(['a' => 1, 'b' => 2], \JSON_THROW_ON_ERROR));

        self::assertSame('customer.collection.' . $expected, $key);
    }

    public function testBuildSupportsCustomNamespaces(): void
    {
        self::assertSame('foo.bar.baz', $this->builder->build('foo', 'bar', 'baz'));
    }

    public function testHashEmailIsPublic(): void
    {
        $hash = $this->builder->hashEmail('UPPER@example.COM');

        self::assertSame(hash('sha256', 'upper@example.com'), $hash);
    }

    public function testBuildContextFamilyKeyHashesLowercasedIdentifierValue(): void
    {
        $key = $this->builder->buildContextFamilyKey(
            'customer',
            'lookup',
            'email',
            'John@Example.COM'
        );

        self::assertSame(
            'customer.lookup.email.' . hash('sha256', 'john@example.com'),
            $key
        );
    }

    public function testBuildContextFamilyKeyCanPreserveCaseSensitiveIdentifierValue(): void
    {
        $key = $this->builder->buildContextFamilyKey(
            'token',
            'detail',
            'external_id',
            'CaseSensitiveValue',
            false
        );

        self::assertSame(
            'token.detail.external_id.' . hash('sha256', 'CaseSensitiveValue'),
            $key
        );
    }

    public function testBuildRefreshDedupeKeyUsesStableLowercasedIdentifierValue(): void
    {
        $key = $this->builder->buildRefreshDedupeKey(
            'customer',
            'lookup',
            'email',
            'John@Example.COM',
            'repository_refresh'
        );

        self::assertSame(hash('sha256', implode('|', array_map('rawurlencode', [
            'customer',
            'lookup',
            'email',
            'john@example.com',
            'repository_refresh',
        ]))), $key);
    }

    public function testBuildRefreshDedupeKeyEscapesDelimiterSegments(): void
    {
        $key = $this->builder->buildRefreshDedupeKey(
            'customer|crm',
            'lookup',
            'email',
            'User|Example',
            'repository|refresh',
            false
        );

        self::assertSame(hash('sha256', implode('|', array_map('rawurlencode', [
            'customer|crm',
            'lookup',
            'email',
            'User|Example',
            'repository|refresh',
        ]))), $key);
    }
}
