<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\CacheHitMetric;
use App\Shared\Application\Observability\Metric\CacheMissMetric;
use App\Shared\Application\Observability\Metric\CacheRefreshFailedMetric;
use App\Shared\Application\Observability\Metric\CacheRefreshScheduledMetric;
use App\Shared\Application\Observability\Metric\CacheRefreshStaleServedMetric;
use App\Shared\Application\Observability\Metric\CacheRefreshSucceededMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class CacheRefreshMetricTest extends UnitTestCase
{
    public function testCacheHitMetricUsesReadHitDimensions(): void
    {
        $metric = CacheHitMetric::create('customer', 'detail', 2);
        $defaultMetric = CacheHitMetric::create('customer', 'detail');

        self::assertSame('CacheHit', $metric->name());
        self::assertSame(2, $metric->value());
        self::assertSame(1, $defaultMetric->value());
        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
        self::assertSame('customer', $metric->dimensions()->values()->get('Context'));
        self::assertSame('detail', $metric->dimensions()->values()->get('Family'));
        self::assertSame('read', $metric->dimensions()->values()->get('Source'));
        self::assertSame('hit', $metric->dimensions()->values()->get('Result'));
    }

    public function testCacheMissMetricUsesReadMissDimensions(): void
    {
        $metric = CacheMissMetric::create('customer', 'lookup');

        self::assertSame('CacheMiss', $metric->name());
        self::assertSame(1, $metric->value());
        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
        self::assertSame('customer', $metric->dimensions()->values()->get('Context'));
        self::assertSame('lookup', $metric->dimensions()->values()->get('Family'));
        self::assertSame('read', $metric->dimensions()->values()->get('Source'));
        self::assertSame('miss', $metric->dimensions()->values()->get('Result'));
    }

    public function testCacheRefreshLifecycleMetricsUseExpectedNamesAndResults(): void
    {
        $this->assertScheduledMetric();
        $this->assertSucceededMetric();
        $this->assertSucceededMetricWithCustomValue();
        $this->assertFailedMetric();
        $this->assertMetric(
            CacheRefreshStaleServedMetric::create('customer', 'detail'),
            'CacheRefreshStaleServed',
            'read',
            'stale_served'
        );
    }

    private function assertScheduledMetric(): void
    {
        $this->assertMetric(
            CacheRefreshScheduledMetric::create('customer', 'detail', 'repository_refresh'),
            'CacheRefreshScheduled',
            'repository_refresh',
            'scheduled'
        );
    }

    private function assertSucceededMetric(): void
    {
        $this->assertMetric(
            CacheRefreshSucceededMetric::create('customer', 'detail', 'repository_refresh'),
            'CacheRefreshSucceeded',
            'repository_refresh',
            'succeeded'
        );
    }

    private function assertSucceededMetricWithCustomValue(): void
    {
        $this->assertMetric(
            CacheRefreshSucceededMetric::create('customer', 'detail', 'repository_refresh', 3),
            'CacheRefreshSucceeded',
            'repository_refresh',
            'succeeded',
            3
        );
    }

    private function assertFailedMetric(): void
    {
        $this->assertMetric(
            CacheRefreshFailedMetric::create('customer', 'detail', 'repository_refresh'),
            'CacheRefreshFailed',
            'repository_refresh',
            'failed'
        );
    }

    private function assertMetric(
        BusinessMetric $metric,
        string $name,
        string $source,
        string $result,
        float|int $value = 1
    ): void {
        self::assertSame($name, $metric->name());
        self::assertSame($value, $metric->value());
        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
        self::assertSame('customer', $metric->dimensions()->values()->get('Context'));
        self::assertSame('detail', $metric->dimensions()->values()->get('Family'));
        self::assertSame($source, $metric->dimensions()->values()->get('Source'));
        self::assertSame($result, $metric->dimensions()->values()->get('Result'));
    }
}
