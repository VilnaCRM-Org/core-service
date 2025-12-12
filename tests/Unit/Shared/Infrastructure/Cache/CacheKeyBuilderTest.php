<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Cache;

use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;

final class CacheKeyBuilderTest extends UnitTestCase
{
    private CacheKeyBuilder $cacheKeyBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheKeyBuilder = new CacheKeyBuilder();
    }

    public function testBuildCreatesKeyFromNamespaceAndParts(): void
    {
        $result = $this->cacheKeyBuilder->build('customer', '12345');

        self::assertSame('customer.12345', $result);
    }

    public function testBuildWithMultipleParts(): void
    {
        $result = $this->cacheKeyBuilder->build('customer', 'email', 'hash123');

        self::assertSame('customer.email.hash123', $result);
    }

    public function testBuildCustomerKey(): void
    {
        $customerId = '01JKX8XGHVDZ46MWYMZT94YER4';
        $result = $this->cacheKeyBuilder->buildCustomerKey($customerId);

        self::assertSame('customer.' . $customerId, $result);
    }

    public function testBuildCustomerEmailKey(): void
    {
        $email = 'test@example.com';
        $expectedHash = hash('sha256', strtolower($email));

        $result = $this->cacheKeyBuilder->buildCustomerEmailKey($email);

        self::assertSame('customer.email.' . $expectedHash, $result);
    }

    public function testHashEmailConvertsToLowercase(): void
    {
        $email1 = 'Test@Example.COM';
        $email2 = 'test@example.com';

        $hash1 = $this->cacheKeyBuilder->hashEmail($email1);
        $hash2 = $this->cacheKeyBuilder->hashEmail($email2);

        self::assertSame($hash1, $hash2);
    }

    public function testHashEmailUsesSha256(): void
    {
        $email = 'test@example.com';
        $expectedHash = hash('sha256', strtolower($email));

        $result = $this->cacheKeyBuilder->hashEmail($email);

        self::assertSame($expectedHash, $result);
        self::assertSame(64, strlen($result)); // SHA256 produces 64 hex characters
    }
}
