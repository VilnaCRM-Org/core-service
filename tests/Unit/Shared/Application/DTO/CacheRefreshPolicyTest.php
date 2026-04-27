<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\DTO;

use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Tests\Unit\UnitTestCase;

final class CacheRefreshPolicyTest extends UnitTestCase
{
    public function testCreateKeepsPolicyPayload(): void
    {
        $policy = CacheRefreshPolicy::create(
            'customer',
            'detail',
            600,
            1.0,
            'stale_while_revalidate',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );

        self::assertSame('customer', $policy->context());
        self::assertSame('detail', $policy->family());
        self::assertSame(600, $policy->ttlSeconds());
        self::assertSame(1.0, $policy->beta());
        self::assertSame('stale_while_revalidate', $policy->consistency());
        self::assertSame(CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH, $policy->refreshSource());
    }
}
