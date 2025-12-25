<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\Collection\MetricCollection;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimensionsInterface;
use App\Shared\Infrastructure\Observability\Collection\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricValueCollection;
use App\Shared\Infrastructure\Observability\Provider\EmfTimestampProvider;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidatorInterface;
use App\Shared\Infrastructure\Observability\Validator\EmfNamespaceValidatorInterface;
use App\Shared\Infrastructure\Observability\ValueObject\EmfAwsMetadata;
use App\Shared\Infrastructure\Observability\ValueObject\EmfCloudWatchMetricConfig;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfNamespaceValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use InvalidArgumentException;

/**
 * Factory for creating EMF payload objects from business metrics
 *
 * Follows SOLID principles:
 * - Single Responsibility: Factory only creates objects, validation delegated to services
 * - Dependency Inversion: Depends on abstractions (EmfNamespaceValidatorInterface, EmfDimensionValueValidatorInterface)
 * - Defensive Programming: Validates namespace at construction time (fail fast)
 */
final readonly class EmfPayloadFactory implements EmfPayloadFactoryInterface
{
    private EmfNamespaceValue $namespace;

    public function __construct(
        string $namespace,
        private EmfTimestampProvider $timestampProvider,
        private EmfNamespaceValidatorInterface $namespaceValidator,
        private EmfDimensionValueValidatorInterface $dimensionValidator
    ) {
        $this->namespace = new EmfNamespaceValue($namespace);
        $this->namespaceValidator->validate($this->namespace);
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
        if ($metrics->isEmpty()) {
            throw new InvalidArgumentException(
                'Cannot create EMF payload from empty metric collection'
            );
        }

        $allMetrics = $metrics->all();
        $dimensions = $this->createDimensionValueCollection($allMetrics[0]->dimensions());
        $payload = $this->createEmptyPayload($dimensions);

        return $this->addMetricsToPayload($payload, $allMetrics);
    }

    private function createEmptyPayload(EmfDimensionValueCollection $dimensions): EmfPayload
    {
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            $this->namespace->value(),
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
        return new EmfMetricDefinition($metric->name(), $metric->unit()->value());
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
            $this->namespace->value(),
            $dimensions->keys(),
            new EmfMetricDefinitionCollection($definition)
        );

        return new EmfAwsMetadata($this->timestampProvider->currentTimestamp(), $cloudWatchConfig);
    }

    private function createDimensionValueCollection(
        MetricDimensionsInterface $dimensions
    ): EmfDimensionValueCollection {
        $dimensionValues = [];
        foreach ($dimensions->values() as $dimension) {
            $dimensionValues[] = new EmfDimensionValue($dimension->key(), $dimension->value());
        }

        return new EmfDimensionValueCollection($this->dimensionValidator, ...$dimensionValues);
    }
}
