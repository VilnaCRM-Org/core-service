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
        $updatedSchema = $this->replaceUlidReference($schema);

        if ($updatedSchema === $schema) {
            return $schemas;
        }

        $schemas[$schemaName] = $updatedSchema;

        return $schemas;
    }

    /**
     * @param array<int|string, SchemaValue> $schema
     *
     * @return array<int|string, SchemaValue>
     */
    private function replaceUlidReference(array $schema): array
    {
        $updatedSchema = $schema;
        $properties = $this->properties($schema);
        $updatedProperties = $this->replaceUlidInProperties($properties);

        if ($updatedProperties !== $properties) {
            $updatedSchema['properties'] = $updatedProperties;
        }

        $allOf = $this->schemaList($schema['allOf'] ?? null);
        $updatedAllOf = [];
        $allOfChanged = false;

        foreach ($allOf as $index => $fragment) {
            $fragmentArray = $this->toArray($fragment);
            $updatedFragment = $this->replaceUlidReference($fragmentArray);
            $updatedAllOf[$index] = $updatedFragment;
            $allOfChanged = $allOfChanged || $updatedFragment !== $fragmentArray;
        }

        if ($allOfChanged) {
            $updatedSchema['allOf'] = $updatedAllOf;
        }

        return $updatedSchema;
    }

    /**
     * @param array<int|string, SchemaValue> $properties
     *
     * @return array<int|string, SchemaValue>
     */
    private function replaceUlidInProperties(array $properties): array
    {
        if (! $this->isSupportedUlidReference($this->reference($properties))) {
            return $properties;
        }

        $properties['ulid'] = ['type' => 'string'];

        return $properties;
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
     * @return array<int, SchemaValue>
     */
    private function schemaList(ArrayObject|array|string|int|float|bool|null $value): array
    {
        $items = SchemaNormalizer::normalize($value);

        return array_values($items);
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
