<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

interface MetricDimensionsInterface
{
    /**
     * @return array<string, string>
     */
    public function toArray(): array;
}
