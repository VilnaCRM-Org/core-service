<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

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
    public static function write(array $constraintViolation, array $properties): array
    {
        $rootProperties = SchemaNormalizer::normalize($constraintViolation['properties'] ?? null);
        $violations = SchemaNormalizer::normalize($rootProperties['violations'] ?? null);
        $items = SchemaNormalizer::normalize($violations['items'] ?? null);
        $items['properties'] = $properties;
        $violations['items'] = $items;
        $rootProperties['violations'] = $violations;
        $constraintViolation['properties'] = $rootProperties;

        return $constraintViolation;
    }
}
