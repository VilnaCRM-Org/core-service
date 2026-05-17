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
    public function extract(array $constraintViolation): ?array
    {
        $items = $this->normalizedItems($constraintViolation);

        return array_key_exists('properties', $items)
            ? (new SchemaNormalizer())->normalize($items['properties'])
            : null;
    }

    /**
     * @param array<string, SchemaValue> $constraintViolation
     *
     * @return array<string, SchemaValue>
     */
    private function normalizedItems(array $constraintViolation): array
    {
        $properties = $this->normalizedSchemaValue($constraintViolation, 'properties');
        $violations = $this->normalizedSchemaValue($properties, 'violations');

        return $this->normalizedSchemaValue($violations, 'items');
    }

    /**
     * @param array<string, SchemaValue> $schema
     *
     * @return array<string, SchemaValue>
     */
    private function normalizedSchemaValue(array $schema, string $key): array
    {
        return match (true) {
            array_key_exists($key, $schema) => (new SchemaNormalizer())->normalize($schema[$key]),
            default => [],
        };
    }
}
