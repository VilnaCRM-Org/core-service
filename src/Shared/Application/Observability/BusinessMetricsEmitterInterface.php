<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability;

interface BusinessMetricsEmitterInterface
{
    /**
     * @param array<string, string> $dimensions
     */
    public function emit(
        string $metricName,
        float|int $value,
        array $dimensions = [],
        string $unit = 'Count'
    ): void;

    /**
     * @param array<string, array{value: float|int, unit?: string}> $metrics
     * @param array<string, string> $dimensions
     */
    public function emitMultiple(array $metrics, array $dimensions = []): void;
}
