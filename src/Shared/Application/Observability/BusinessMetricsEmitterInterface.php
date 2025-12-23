<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricCollection;

interface BusinessMetricsEmitterInterface
{
    /**
     * Emit a single business metric
     */
    public function emit(BusinessMetric $metric): void;

    /**
     * Emit multiple business metrics together
     */
    public function emitCollection(MetricCollection $metrics): void;
}
