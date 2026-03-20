<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 */
final class HydraAllOfItemUpdater
{
    public function __construct(
        private HydraAtTypeExampleUpdater $exampleUpdater
    ) {
    }

    public function update(ArrayObject|array $item): ?ArrayObject
    {
        $normalizedItem = SchemaNormalizer::normalize($item);
        $properties = $this->extractAndNormalizeProperties($normalizedItem);
        $viewSchema = $this->extractAndNormalizeView($properties);
        $example = $this->extractAndUpdateExample($viewSchema);

        if ($example === null) {
            return null;
        }

        $viewSchema['example'] = $example;
        $properties['view'] = new ArrayObject($viewSchema);
        $normalizedItem['properties'] = $properties;

        return new ArrayObject($normalizedItem);
    }

    private function extractAndNormalizeProperties(array $normalizedItem): array
    {
        if (! array_key_exists('properties', $normalizedItem)) {
            $normalizedItem['properties'] = null;
        }

        return SchemaNormalizer::normalize($normalizedItem['properties']);
    }

    private function extractAndNormalizeView(array $properties): array
    {
        if (! array_key_exists('view', $properties)) {
            $properties['view'] = null;
        }

        return SchemaNormalizer::normalize($properties['view']);
    }

    private function extractAndUpdateExample(array $viewSchema): ?array
    {
        if (! array_key_exists('example', $viewSchema)) {
            $viewSchema['example'] = null;
        }

        return $this->exampleUpdater->update(
            SchemaNormalizer::normalize($viewSchema['example'])
        );
    }
}
