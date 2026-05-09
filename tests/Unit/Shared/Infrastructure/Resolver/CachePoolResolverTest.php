<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver;

use App\Shared\Infrastructure\Resolver\CachePoolResolver;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class CachePoolResolverTest extends UnitTestCase
{
    private ServiceProviderInterface&MockObject $cachePools;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachePools = $this->createMock(ServiceProviderInterface::class);
    }

    public function testResolveReturnsContextCachePool(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $resolver = new CachePoolResolver($this->cachePools);

        $this->cachePools
            ->expects($this->once())
            ->method('has')
            ->with('customer')
            ->willReturn(true);
        $this->cachePools
            ->expects($this->once())
            ->method('get')
            ->with('customer')
            ->willReturn($cache);

        self::assertSame($cache, $resolver->resolve('customer'));
    }

    public function testResolveRejectsUnknownContext(): void
    {
        $resolver = new CachePoolResolver($this->cachePools);

        $this->cachePools
            ->expects($this->once())
            ->method('has')
            ->with('invoice')
            ->willReturn(false);
        $this->cachePools
            ->expects($this->never())
            ->method('get');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No cache pool registered for context "invoice".');

        $resolver->resolve('invoice');
    }

    public function testResolveRejectsCachePoolWithoutTags(): void
    {
        $resolver = new CachePoolResolver($this->cachePools);

        $this->cachePools
            ->expects($this->once())
            ->method('has')
            ->with('customer')
            ->willReturn(true);
        $this->cachePools
            ->expects($this->once())
            ->method('get')
            ->with('customer')
            ->willReturn(new \stdClass());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cache pool registered for context "customer" must support tags.'
        );

        $resolver->resolve('customer');
    }
}
