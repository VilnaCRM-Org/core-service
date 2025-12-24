<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricValueCollection;
use App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException;

/**
 * Complete EMF payload structure for AWS CloudWatch
 *
 * Combines _aws metadata, dimension values, and metric values into
 * a JSON-serializable object that can be logged for CloudWatch ingestion.
 * Validates that dimension keys and metric names don't collide.
 */
final readonly class EmfPayload implements \JsonSerializable
{
    private const string RESERVED_AWS_KEY = '_aws';

    public function __construct(
        private EmfAwsMetadata $awsMetadata,
        private EmfDimensionValueCollection $dimensionValues,
        private EmfMetricValueCollection $metricValues
    ) {
        $this->validateNoKeyCollisions();
    }

    public function awsMetadata(): EmfAwsMetadata
    {
        return $this->awsMetadata;
    }

    public function dimensionValues(): EmfDimensionValueCollection
    {
        return $this->dimensionValues;
    }

    public function metricValues(): EmfMetricValueCollection
    {
        return $this->metricValues;
    }

    public function withAddedMetric(
        EmfMetricDefinition $definition,
        EmfMetricValue $value
    ): self {
        $updatedConfig = $this->awsMetadata
            ->cloudWatchMetricConfig()
            ->withAddedMetric($definition);

        return new self(
            $this->awsMetadata->withUpdatedConfig($updatedConfig),
            $this->dimensionValues,
            $this->metricValues->add($value)
        );
    }

    /**
     * @return array<string, string|int|float|array<string, int|array<int, array<string, string|array<int, array<int, string>|array<int, array<string, string>>>>>>>
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            ['_aws' => $this->awsMetadata->jsonSerialize()],
            $this->dimensionValues->toAssociativeArray(),
            $this->metricValues->toAssociativeArray()
        );
    }

    private function validateNoKeyCollisions(): void
    {
        $dimensionKeys = array_keys($this->dimensionValues->toAssociativeArray());
        $metricNames = array_keys($this->metricValues->toAssociativeArray());

        $collisions = array_intersect($dimensionKeys, $metricNames);
        if ($collisions !== []) {
            throw EmfKeyCollisionException::dimensionMetricCollision($collisions);
        }

        $allKeys = [...$dimensionKeys, ...$metricNames];
        if (in_array(self::RESERVED_AWS_KEY, $allKeys, true)) {
            throw EmfKeyCollisionException::reservedKeyUsed(self::RESERVED_AWS_KEY);
        }
    }
}
