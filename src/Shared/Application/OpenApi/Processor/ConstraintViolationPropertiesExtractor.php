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
    public static function extract(array $constraintViolation): ?array
    {
        $items = self::normalizedItems($constraintViolation);

        return match (true) {
            array_key_exists('properties', $items) => SchemaNormalizer::normalize(
                $items['properties']
            ),
            default => null,
        };
    }

    /**
     * @param array<string, SchemaValue> $constraintViolation
     *
     * @return array<string, SchemaValue>
     */
    private static function normalizedItems(array $constraintViolation): array
    {
        $properties = self::normalizedSchemaValue($constraintViolation, 'properties');
        $violations = self::normalizedSchemaValue($properties, 'violations');

        return self::normalizedSchemaValue($violations, 'items');
    }

    /**
     * @param array<string, SchemaValue> $schema
     *
     * @return array<string, SchemaValue>
     */
    private static function normalizedSchemaValue(array $schema, string $key): array
    {
        return match (true) {
            array_key_exists($key, $schema) => SchemaNormalizer::normalize($schema[$key]),
            default => [],
        };
    }
}
