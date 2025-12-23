<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Metric;

use App\Core\Customer\Application\Metric\CustomersCreatedMetric;
use App\Shared\Application\Observability\Metric\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class CustomersCreatedMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new CustomersCreatedMetric();

        self::assertSame('CustomersCreated', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new CustomersCreatedMetric();

        $dimensions = $metric->dimensions();

        self::assertSame('Customer', $dimensions['Endpoint']);
        self::assertSame('create', $dimensions['Operation']);
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new CustomersCreatedMetric();

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new CustomersCreatedMetric(5);

        self::assertSame(5, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new CustomersCreatedMetric();

        self::assertSame(MetricUnit::COUNT, $metric->unit());
    }
}
