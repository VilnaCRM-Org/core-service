<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;

final class BusinessMetricsEmitterSpy implements BusinessMetricsEmitterInterface
{
    /** @var array<int, array{name: string, value: float|int, dimensions: array<string, string>, unit: string}> */
    private array $emitted = [];

    public function emit(
        string $metricName,
        float|int $value,
        array $dimensions = [],
        string $unit = 'Count'
    ): void {
        $this->emitted[] = [
            'name' => $metricName,
            'value' => $value,
            'dimensions' => $dimensions,
            'unit' => $unit,
        ];
    }

    public function emitMultiple(array $metrics, array $dimensions = []): void
    {
        foreach ($metrics as $name => $config) {
            $this->emit($name, $config['value'], $dimensions, $config['unit'] ?? 'Count');
        }
    }

    public function clear(): void
    {
        $this->emitted = [];
    }

    /**
     * @return array<int, array{name: string, value: float|int, dimensions: array<string, string>, unit: string}>
     */
    public function emitted(): array
    {
        return $this->emitted;
    }

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

        throw new \AssertionError("Metric '{$metricName}' with specified dimensions was not emitted");
    }
}
