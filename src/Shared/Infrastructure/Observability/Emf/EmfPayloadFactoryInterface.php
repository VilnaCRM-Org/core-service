<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Emf;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricCollection;

/**
 * Interface for creating EMF payload objects from business metrics
 */
interface EmfPayloadFactoryInterface
{
    /**
     * Creates an EMF payload from a single business metric
     */
    public function createFromMetric(BusinessMetric $metric): EmfPayload;

    /**
     * Creates an EMF payload from a collection of business metrics
     */
    public function createFromCollection(MetricCollection $metrics): EmfPayload;
}
