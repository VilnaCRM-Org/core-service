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
        $schema = $normalizedSchemas[self::HYDRA_COLLECTION_SCHEMA] ?? null;
        $normalized = SchemaNormalizer::normalize($schema);
        $allOf = $normalized['allOf'] ?? null;
        if (!is_array($allOf)) {
            return $schemas;
        }

        foreach ($allOf as $index => $item) {
            $normalizedItem = SchemaNormalizer::normalize($item);
            $properties = $normalizedItem['properties'] ?? null;
            if (!is_array($properties)) {
                continue;
            }

            $viewSchema = SchemaNormalizer::normalize($properties['view'] ?? null);
            if ($viewSchema === []) {
                continue;
            }

            $example = $viewSchema['example'] ?? null;
            if (!is_array($example) || !array_key_exists('type', $example)) {
                continue;
            }

            if (array_key_exists('@type', $example)) {
                return $schemas;
            }

            $example['@type'] = $example['type'];
            unset($example['type']);

            $viewSchema['example'] = $example;
            $properties['view'] = new ArrayObject($viewSchema);
            $normalizedItem['properties'] = $properties;
            $allOf[$index] = new ArrayObject($normalizedItem);
            $normalized['allOf'] = $allOf;
            $normalizedSchemas[self::HYDRA_COLLECTION_SCHEMA] = new ArrayObject($normalized);

            return new ArrayObject($normalizedSchemas);
        }

        return new ArrayObject($normalizedSchemas);
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
