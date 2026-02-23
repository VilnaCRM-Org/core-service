<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class OpenApiSchemaFixesProcessor
{
    private const HYDRA_COLLECTION_SCHEMA = 'HydraCollectionBaseSchema';
    private const ULID_SCHEMA = 'UlidInterface.jsonld-output';

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas() ?? new ArrayObject();

        $schemas = $this->fixHydraViewExample($schemas);
        $schemas = $this->forceUlidSchemaToString($schemas);

        return $openApi->withComponents($components->withSchemas($schemas));
    }

    /**
     * @param ArrayObject<string, array|bool|float|int|string|ArrayObject|null> $schemas
     */
    private function fixHydraViewExample(ArrayObject $schemas): ArrayObject
    {
        $normalizedSchemas = $schemas->getArrayCopy();
        $normalized = $this->normalizeHydraSchema($normalizedSchemas);
        if ($normalized === null) {
            return $schemas;
        }

        $updated = $this->withHydraViewTypeExample($normalized);
        if ($updated === null) {
            return new ArrayObject($normalizedSchemas);
        }

        $normalizedSchemas[self::HYDRA_COLLECTION_SCHEMA] = new ArrayObject($updated);
        return new ArrayObject($normalizedSchemas);
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, mixed>|null
     */
    private function normalizeHydraSchema(array $schemas): ?array
    {
        $schema = $schemas[self::HYDRA_COLLECTION_SCHEMA] ?? null;
        $normalized = SchemaNormalizer::normalize($schema);
        $allOf = $normalized['allOf'] ?? null;
        if (!is_array($allOf)) {
            return null;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $normalized
     *
     * @return array<string, mixed>|null
     */
    private function withHydraViewTypeExample(array $normalized): ?array
    {
        $allOf = $normalized['allOf'] ?? null;
        $updatedAllOf = $this->updateHydraAllOf($allOf);
        if ($updatedAllOf === null) {
            return null;
        }

        $normalized['allOf'] = $updatedAllOf;

        return $normalized;
    }

    /**
     * @param array<int, mixed> $allOf
     *
     * @return array<int, mixed>|null
     */
    private function updateHydraAllOf(array $allOf): ?array
    {
        foreach ($allOf as $index => $item) {
            $updatedItem = $this->updateHydraAllOfItem($item);
            if ($updatedItem === null) {
                continue;
            }

            $allOf[$index] = $updatedItem;

            return $allOf;
        }

        return null;
    }

    private function updateHydraAllOfItem(mixed $item): ?ArrayObject
    {
        $normalizedItem = SchemaNormalizer::normalize($item);
        $properties = $normalizedItem['properties'] ?? null;
        if (!is_array($properties)) {
            return null;
        }

        $viewSchema = SchemaNormalizer::normalize($properties['view'] ?? null);
        if ($viewSchema === []) {
            return null;
        }

        $example = $this->withAtTypeExample($viewSchema['example'] ?? null);
        if ($example === null) {
            return null;
        }

        $viewSchema['example'] = $example;
        $properties['view'] = new ArrayObject($viewSchema);
        $normalizedItem['properties'] = $properties;

        return new ArrayObject($normalizedItem);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function withAtTypeExample(mixed $example): ?array
    {
        if (!is_array($example) || !array_key_exists('type', $example)) {
            return null;
        }

        if (array_key_exists('@type', $example)) {
            return null;
        }

        $example['@type'] = $example['type'];
        unset($example['type']);

        return $example;
    }

    /**
     * @param ArrayObject<string, array|bool|float|int|string|ArrayObject|null> $schemas
     */
    private function forceUlidSchemaToString(ArrayObject $schemas): ArrayObject
    {
        $normalizedSchemas = $schemas->getArrayCopy();
        $schema = $normalizedSchemas[self::ULID_SCHEMA] ?? null;
        $normalized = SchemaNormalizer::normalize($schema);
        if ($normalized === []) {
            return $schemas;
        }

        $normalizedSchemas[self::ULID_SCHEMA] = new ArrayObject([
            'type' => 'string',
            'description' => $normalized['description'] ?? '',
            'deprecated' => $normalized['deprecated'] ?? false,
        ]);

        return new ArrayObject($normalizedSchemas);
    }
}
