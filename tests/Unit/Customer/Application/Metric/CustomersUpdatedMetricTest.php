<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Metric;

use App\Core\Customer\Application\Metric\CustomersUpdatedMetric;
use App\Shared\Application\Observability\Metric\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class CustomersUpdatedMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new CustomersUpdatedMetric();

        self::assertSame('CustomersUpdated', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new CustomersUpdatedMetric();

        $dimensions = $metric->dimensions()->toArray();

        self::assertSame('Customer', $dimensions['Endpoint']);
        self::assertSame('update', $dimensions['Operation']);
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new CustomersUpdatedMetric();

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new CustomersUpdatedMetric(5);

        self::assertSame(5, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new CustomersUpdatedMetric();

        self::assertSame(MetricUnit::COUNT, $metric->unit());
    }
}
