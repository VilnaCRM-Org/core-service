<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class BusinessMetricTest extends UnitTestCase
{
    public function testDefaultsWhenMetricIsUninitialized(): void
    {
        $reflection = new \ReflectionClass(TestBusinessMetric::class);
        $metric = $reflection->newInstanceWithoutConstructor();

        self::assertSame(0, $metric->value());
        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }
}
