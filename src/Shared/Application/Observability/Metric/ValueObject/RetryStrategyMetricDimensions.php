<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

use App\Shared\Application\Observability\Metric\Collection\MetricDimensions;

final readonly class RetryStrategyMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(
        private string $operation,
        private string $messageType,
        private string $exceptionType
    ) {
    }

    public function values(): MetricDimensions
    {
        return new MetricDimensions(
            new MetricDimension('Endpoint', 'Messenger'),
            new MetricDimension('Operation', $this->operation),
            new MetricDimension('MessageType', $this->extractClassName($this->messageType)),
            new MetricDimension('ExceptionType', $this->extractClassName($this->exceptionType))
        );
    }

    private function extractClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
