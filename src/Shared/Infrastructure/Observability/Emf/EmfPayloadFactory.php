<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Emf;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricCollection;
use App\Shared\Application\Observability\Metric\MetricDimensionsInterface;

/**
 * Factory for creating EMF payload objects from business metrics
 */
final readonly class EmfPayloadFactory implements EmfPayloadFactoryInterface
{
    public function __construct(
        private string $namespace,
        private EmfTimestampProvider $timestampProvider
    ) {
    }

    public function createFromMetric(BusinessMetric $metric): EmfPayload
    {
        $dimensionValueCollection = $this->createDimensionValueCollection($metric->dimensions());
        $metricDefinition = $this->createMetricDefinition($metric);
        $awsMetadata = $this->createAwsMetadata($dimensionValueCollection, $metricDefinition);

        return new EmfPayload(
            $awsMetadata,
            $dimensionValueCollection,
            new EmfMetricValueCollection($this->createMetricValue($metric))
        );
    }

    public function createFromCollection(MetricCollection $metrics): EmfPayload
    {
        $allMetrics = $metrics->all();
        $dimensions = $this->createDimensionValueCollection($allMetrics[0]->dimensions());
        $payload = $this->createEmptyPayload($dimensions);

        return $this->addMetricsToPayload($payload, $allMetrics);
    }

    private function createEmptyPayload(EmfDimensionValueCollection $dimensions): EmfPayload
    {
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            $this->namespace,
            $dimensions->keys(),
            new EmfMetricDefinitionCollection()
        );
        $timestamp = $this->timestampProvider->currentTimestamp();
        $awsMetadata = new EmfAwsMetadata($timestamp, $cloudWatchConfig);

        return new EmfPayload($awsMetadata, $dimensions, new EmfMetricValueCollection());
    }

    /**
     * @param array<int, BusinessMetric> $metrics
     */
    private function addMetricsToPayload(EmfPayload $payload, array $metrics): EmfPayload
    {
        foreach ($metrics as $metric) {
            $payload = $payload->withAddedMetric(
                $this->createMetricDefinition($metric),
                $this->createMetricValue($metric)
            );
        }

        return $payload;
    }

    private function createMetricDefinition(BusinessMetric $metric): EmfMetricDefinition
    {
        return new EmfMetricDefinition($metric->name(), $metric->unit()->value);
    }

    private function createMetricValue(BusinessMetric $metric): EmfMetricValue
    {
        return new EmfMetricValue($metric->name(), $metric->value());
    }

    private function createAwsMetadata(
        EmfDimensionValueCollection $dimensions,
        EmfMetricDefinition $definition
    ): EmfAwsMetadata {
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            $this->namespace,
            $dimensions->keys(),
            new EmfMetricDefinitionCollection($definition)
        );

        return new EmfAwsMetadata($this->timestampProvider->currentTimestamp(), $cloudWatchConfig);
    }

    private function createDimensionValueCollection(
        MetricDimensionsInterface $dimensions
    ): EmfDimensionValueCollection {
        $dimensionValues = [];
        foreach ($dimensions->toArray() as $key => $value) {
            $dimensionValues[] = new EmfDimensionValue($key, $value);
        }

        return new EmfDimensionValueCollection(...$dimensionValues);
    }
}
