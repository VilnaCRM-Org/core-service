<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricUnit;

/**
 * Test metric for OrderValue
 */
final readonly class TestOrderValueMetric extends BusinessMetric
{
    public function __construct(float|int $value)
    {
        parent::__construct($value, MetricUnit::NONE);
    }

    public function name(): string
    {
        return 'OrderValue';
    }

    /**
     * @return array<string, string>
     */
    public function dimensions(): array
    {
        return ['Endpoint' => 'Order', 'Operation' => 'create'];
    }
}
