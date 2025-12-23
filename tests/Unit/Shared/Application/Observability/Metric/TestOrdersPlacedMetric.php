<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricUnit;

/**
 * Test metric for OrdersPlaced
 */
final readonly class TestOrdersPlacedMetric extends BusinessMetric
{
    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'OrdersPlaced';
    }

    /**
     * @return array<string, string>
     */
    public function dimensions(): array
    {
        return ['Endpoint' => 'Order', 'Operation' => 'create'];
    }
}
