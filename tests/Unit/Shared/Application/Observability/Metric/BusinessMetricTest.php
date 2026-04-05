<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class BusinessMetricTest extends UnitTestCase
{
    public function testMetricReturnsProvidedValues(): void
    {
        $unit = new MetricUnit(MetricUnit::SECONDS);
        $metric = new TestBusinessMetric(42, $unit);

        self::assertSame(42, $metric->value());
        self::assertSame(MetricUnit::SECONDS, $metric->unit()->value());
    }
}
