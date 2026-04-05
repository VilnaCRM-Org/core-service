<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Updater;

use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 */
final class HydraDirectViewExampleUpdater
{
    /**
     * @param array<string, SchemaValue> $normalized
     *
     * @return array<string, SchemaValue>|null
     */
    public function update(array $normalized): ?array
    {
        $properties = $this->normalizedSchemaValue($normalized, 'properties');
        $viewSchema = $this->normalizedSchemaValue($properties, 'view');
        $updatedExample = $this->updatedExample($viewSchema);

        return match (true) {
            $updatedExample === null => null,
            default => $this->updatedNormalized(
                $normalized,
                $properties,
                $viewSchema,
                $updatedExample
            ),
        };
    }

    /**
     * @param array<string, SchemaValue> $viewSchema
     *
     * @return array<string, SchemaValue>|null
     */
    private function updatedExample(array $viewSchema): ?array
    {
        $example = $this->normalizedSchemaValue($viewSchema, 'example');

        return match (true) {
            ! array_key_exists('type', $example) => null,
            default => $this->updatedTypeExample($example),
        };
    }

    /**
     * @param array<string, SchemaValue> $normalized
     * @param array<string, SchemaValue> $properties
     * @param array<string, SchemaValue> $viewSchema
     * @param array<string, SchemaValue> $updatedExample
     *
     * @return array<string, SchemaValue>
     */
    private function updatedNormalized(
        array $normalized,
        array $properties,
        array $viewSchema,
        array $updatedExample
    ): array {
        $viewSchema['example'] = $updatedExample;
        $properties['view'] = $viewSchema;
        $normalized['properties'] = $properties;

        return $normalized;
    }

    /**
     * @param array<string, SchemaValue> $example
     *
     * @return array<string, SchemaValue>
     */
    private function updatedTypeExample(array $example): array
    {
        $example['@type'] ??= $example['type'];
        unset($example['type']);

        return $example;
    }

    /**
     * @param array<string, SchemaValue> $schema
     *
     * @return array<string, SchemaValue>
     */
    private function normalizedSchemaValue(array $schema, string $key): array
    {
        return match (true) {
            array_key_exists($key, $schema) => SchemaNormalizer::normalize($schema[$key]),
            default => [],
        };
    }
}
