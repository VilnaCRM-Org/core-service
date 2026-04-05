<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class CustomerUlidRefReplacer
{
    /**
     * @param array<string, SchemaValue> $schemas
     *
     * @return array<string, SchemaValue>
     */
    public function replace(array $schemas, string $schemaName): array
    {
        $schema = $this->toArray($schemas[$schemaName] ?? []);
        $properties = $this->properties($schema);
        $ref = $this->reference($properties);

        if (! $this->isSupportedUlidReference($ref)) {
            return $schemas;
        }

        $properties['ulid'] = ['type' => 'string'];
        $schema['properties'] = $properties;
        $schemas[$schemaName] = $schema;

        return $schemas;
    }

    /**
     * @param array<int|string, SchemaValue> $schema
     *
     * @return array<int|string, SchemaValue>
     */
    private function properties(array $schema): array
    {
        return $this->toArray($schema['properties'] ?? null);
    }

    /**
     * @param array<int|string, SchemaValue> $properties
     */
    private function reference(array $properties): string
    {
        $ref = $this->ulidProperty($properties)['$ref'] ?? null;

        return is_string($ref) ? $ref : '';
    }

    /**
     * @param array<int|string, SchemaValue> $properties
     *
     * @return array<int|string, SchemaValue>
     */
    private function ulidProperty(array $properties): array
    {
        return $this->toArray($properties['ulid'] ?? null);
    }

    /**
     * @param SchemaValue $value
     *
     * @return array<int|string, SchemaValue>
     */
    private function toArray($value): array
    {
        return SchemaNormalizer::normalize($value);
    }

    private function isSupportedUlidReference(string $ref): bool
    {
        return preg_match('~^#/components/schemas/UlidInterface(?:\.jsonld-output)?$~', $ref) === 1;
    }
}
