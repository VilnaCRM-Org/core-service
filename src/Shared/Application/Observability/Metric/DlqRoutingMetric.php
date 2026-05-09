<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Shared\Application\Observability\Metric\ValueObject\RetryStrategyMetricDimensions;

final readonly class DlqRoutingMetric extends BusinessMetric
{
    private function __construct(
        private string $messageType,
        private string $exceptionType,
        float|int $value = 1
    ) {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public static function create(
        string $messageType,
        string $exceptionType,
        float|int $value = 1
    ): self {
        return new self($messageType, $exceptionType, $value);
    }

    public function name(): string
    {
        return 'MessengerDlqRoutings';
    }

    public function dimensions(): RetryStrategyMetricDimensions
    {
        return new RetryStrategyMetricDimensions(
            operation: 'dlq',
            messageType: $this->messageType,
            exceptionType: $this->exceptionType
        );
    }
}
