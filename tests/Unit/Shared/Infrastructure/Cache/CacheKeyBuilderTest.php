<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Cache;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use PHPUnit\Framework\TestCase;

final class CacheKeyBuilderTest extends TestCase
{
    private CacheKeyBuilder $builder;

    #[\Override]
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
}
