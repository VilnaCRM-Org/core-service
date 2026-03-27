<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

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
        $properties = SchemaNormalizer::normalize($normalized['properties'] ?? null);
        $viewSchema = SchemaNormalizer::normalize($properties['view'] ?? null);
        $example = SchemaNormalizer::normalize($viewSchema['example'] ?? null);

        if (! array_key_exists('type', $example)) {
            return null;
        }

        $example['@type'] ??= $example['type'];
        unset($example['type']);

        $viewSchema['example'] = $example;
        $properties['view'] = $viewSchema;
        $normalized['properties'] = $properties;

        return $normalized;
    }
}
