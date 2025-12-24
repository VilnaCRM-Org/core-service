<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Metric;

use App\Core\Customer\Application\Metric\CustomersCreatedMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\UnitTestCase;

final class CustomersCreatedMetricTest extends UnitTestCase
{
    public function testReturnsCorrectMetricName(): void
    {
        $metric = new CustomersCreatedMetric(new MetricDimensionsFactory());

        self::assertSame('CustomersCreated', $metric->name());
    }

    public function testReturnsCorrectDimensions(): void
    {
        $metric = new CustomersCreatedMetric(new MetricDimensionsFactory());

        $dimensions = $metric->dimensions()->values();

        self::assertSame('Customer', $dimensions->get('Endpoint'));
        self::assertSame('create', $dimensions->get('Operation'));
    }

    public function testDefaultsToValueOfOne(): void
    {
        $metric = new CustomersCreatedMetric(new MetricDimensionsFactory());

        self::assertSame(1, $metric->value());
    }

    public function testAcceptsCustomValue(): void
    {
        $metric = new CustomersCreatedMetric(new MetricDimensionsFactory(), 5);

        self::assertSame(5, $metric->value());
    }

    public function testUsesCountUnit(): void
    {
        $metric = new CustomersCreatedMetric(new MetricDimensionsFactory());

        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
    }
}
