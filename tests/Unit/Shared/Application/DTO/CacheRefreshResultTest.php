<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\DTO;

use App\Shared\Application\DTO\CacheRefreshResult;
use App\Tests\Unit\UnitTestCase;

final class CacheRefreshResultTest extends UnitTestCase
{
    public function testSuccessKeepsResultPayload(): void
    {
        $result = CacheRefreshResult::success('customer', 'detail', 'dedupe-1');

        self::assertSame('customer', $result->context());
        self::assertSame('detail', $result->family());
        self::assertSame('dedupe-1', $result->dedupeKey());
        self::assertTrue($result->refreshed());
        self::assertSame('refreshed', $result->reason());
    }

    public function testSkippedKeepsReason(): void
    {
        $result = CacheRefreshResult::skipped(
            'customer',
            'lookup',
            'dedupe-2',
            'cache_unavailable'
        );

        self::assertSame('customer', $result->context());
        self::assertSame('lookup', $result->family());
        self::assertSame('dedupe-2', $result->dedupeKey());
        self::assertFalse($result->refreshed());
        self::assertSame('cache_unavailable', $result->reason());
    }
}
