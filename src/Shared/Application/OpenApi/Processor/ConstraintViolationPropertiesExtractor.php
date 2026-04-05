<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class ConstraintViolationPropertiesExtractor
{
    /**
     * @param array<string, SchemaValue> $constraintViolation
     *
     * @return array<string, SchemaValue>|null
     */
    public static function extract($constraintViolation)
    {
        $items = self::normalizedItems($constraintViolation);

        return array_key_exists('properties', $items)
            ? SchemaNormalizer::normalize($items['properties'])
            : null;
    }

    /**
     * @param array<string, SchemaValue> $constraintViolation
     *
     * @return array<string, SchemaValue>
     */
    private static function normalizedItems($constraintViolation)
    {
        $properties = SchemaNormalizer::normalize($constraintViolation['properties'] ?? null);
        $violations = SchemaNormalizer::normalize($properties['violations'] ?? null);

        return SchemaNormalizer::normalize($violations['items'] ?? null);
    }
}
