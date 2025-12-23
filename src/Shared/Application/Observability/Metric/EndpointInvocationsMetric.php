<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;

/**
 * Metric for tracking API endpoint invocations
 */
final readonly class EndpointInvocationsMetric extends EndpointOperationBusinessMetric
{
    public function __construct(
        MetricDimensionsFactoryInterface $dimensionsFactory,
        private string $endpoint,
        private string $operation,
        float|int $value = 1
    ) {
        parent::__construct($dimensionsFactory, $value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'EndpointInvocations';
    }

    protected function endpoint(): string
    {
        return $this->endpoint;
    }

    protected function operation(): string
    {
        return $this->operation;
    }
}
