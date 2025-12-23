<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

/**
 * Metric for tracking API endpoint invocations
 */
final readonly class EndpointInvocationsMetric extends BusinessMetric
{
    public function __construct(
        private string $endpoint,
        private string $operation,
        float|int $value = 1
    ) {
        parent::__construct($value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'EndpointInvocations';
    }

    /**
     * @return array<string, string>
     */
    public function dimensions(): array
    {
        return [
            'Endpoint' => $this->endpoint,
            'Operation' => $this->operation,
        ];
    }
}
