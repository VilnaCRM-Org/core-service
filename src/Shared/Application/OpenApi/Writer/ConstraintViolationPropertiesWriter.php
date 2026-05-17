<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Writer;

use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class ConstraintViolationPropertiesWriter
{
    /**
     * @param array<string, SchemaValue> $constraintViolation
     * @param array<string, SchemaValue> $properties
     *
     * @return array<string, SchemaValue>
     */
    public function write(array $constraintViolation, array $properties): array
    {
        $schemaNormalizer = new SchemaNormalizer();
        $constraintViolation = $schemaNormalizer->normalize($constraintViolation);
        $rootProperties = $this->normalizedRootProperties($constraintViolation);
        $violations = $this->normalizedViolations($rootProperties);
        $items = $schemaNormalizer->normalize($violations['items'] ?? null);
        $items['properties'] = $properties;
        $violations['items'] = $items;
        $rootProperties['violations'] = $violations;
        $constraintViolation['properties'] = $rootProperties;

        return $constraintViolation;
    }

    /**
     * @param array<string, SchemaValue> $constraintViolation
     *
     * @return array<string, SchemaValue>
     */
    private function normalizedRootProperties(array $constraintViolation): array
    {
        return (new SchemaNormalizer())->normalize($constraintViolation['properties'] ?? null);
    }

    /**
     * @param array<string, SchemaValue> $rootProperties
     *
     * @return array<string, SchemaValue>
     */
    private function normalizedViolations(array $rootProperties): array
    {
        return (new SchemaNormalizer())->normalize($rootProperties['violations'] ?? null);
    }
}
