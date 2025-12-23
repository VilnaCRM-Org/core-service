<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricCollection;

/**
 * @phpstan-type MetricRecord array{
 *     name: string,
 *     value: float|int,
 *     dimensions: array<string, string>,
 *     unit: string
 * }
 */
final class BusinessMetricsEmitterSpy implements BusinessMetricsEmitterInterface
{
    /** @var array<int, MetricRecord> */
    private array $emitted = [];

    public function emit(BusinessMetric $metric): void
    {
        $this->emitted[] = [
            'name' => $metric->name(),
            'value' => $metric->value(),
            'dimensions' => $metric->dimensions(),
            'unit' => $metric->unit()->value,
        ];
    }

    public function emitCollection(MetricCollection $metrics): void
    {
        foreach ($metrics as $metric) {
            $this->emit($metric);
        }
    }

    public function clear(): void
    {
        $this->emitted = [];
    }

    /**
     * @return array<int, MetricRecord>
     */
    public function emitted(): array
    {
        return $this->emitted;
    }

    /**
     * @param array<string, string> $dimensions
     */
    public function assertEmittedWithDimensions(string $metricName, array $dimensions): void
    {
        foreach ($this->emitted as $metric) {
            if (
                $metric['name'] === $metricName
                && array_intersect_assoc($metric['dimensions'], $dimensions) === $dimensions
            ) {
                return;
            }
        }

        $message = "Metric '{$metricName}' with specified dimensions was not emitted";
        throw new \AssertionError($message);
    }
}
