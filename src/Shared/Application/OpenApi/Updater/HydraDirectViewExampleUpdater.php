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
    public function update($normalized)
    {
        $properties = SchemaNormalizer::normalize($normalized['properties'] ?? null);
        $viewSchema = SchemaNormalizer::normalize($properties['view'] ?? null);
        $updatedExample = $this->updatedExample($viewSchema);

        if ($updatedExample === null) {
            return null;
        }

        $viewSchema['example'] = $updatedExample;
        $properties['view'] = $viewSchema;
        $normalized['properties'] = $properties;

        return $normalized;
    }

    /**
     * @param array<string, SchemaValue> $viewSchema
     *
     * @return array<string, SchemaValue>|null
     */
    private function updatedExample($viewSchema)
    {
        $example = SchemaNormalizer::normalize($viewSchema['example'] ?? null);

        if (! array_key_exists('type', $example)) {
            return null;
        }

        $example['@type'] ??= $example['type'];
        unset($example['type']);

        return $example;
    }
}
