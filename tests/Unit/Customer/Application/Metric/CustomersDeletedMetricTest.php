<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Metric;

use App\Core\Customer\Application\Metric\CustomersDeletedMetric;
use App\Shared\Application\Observability\Metric\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class CustomersDeletedMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new CustomersDeletedMetric();

        self::assertSame('CustomersDeleted', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new CustomersDeletedMetric();

        $dimensions = $metric->dimensions();

        self::assertSame('Customer', $dimensions['Endpoint']);
        self::assertSame('delete', $dimensions['Operation']);
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new CustomersDeletedMetric();

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new CustomersDeletedMetric(5);

        self::assertSame(5, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new CustomersDeletedMetric();

        self::assertSame(MetricUnit::COUNT, $metric->unit());
    }
}
