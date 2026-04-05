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
    public static function write($constraintViolation, $properties)
    {
        $rootProperties = self::normalizedRootProperties($constraintViolation);
        $violations = self::normalizedViolations($rootProperties);
        $items = SchemaNormalizer::normalize($violations['items'] ?? null);
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
    private static function normalizedRootProperties($constraintViolation)
    {
        return SchemaNormalizer::normalize($constraintViolation['properties'] ?? null);
    }

    /**
     * @param array<string, SchemaValue> $rootProperties
     *
     * @return array<string, SchemaValue>
     */
    private static function normalizedViolations($rootProperties)
    {
        return SchemaNormalizer::normalize($rootProperties['violations'] ?? null);
    }
}
