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
        if (! array_key_exists('properties', $normalizedItem)) {
            $normalizedItem['properties'] = null;
        }
        $properties = SchemaNormalizer::normalize($normalizedItem['properties']);

        if (! array_key_exists('view', $properties)) {
            $properties['view'] = null;
        }
        $viewSchema = SchemaNormalizer::normalize($properties['view']);

        if (! array_key_exists('example', $viewSchema)) {
            $viewSchema['example'] = null;
        }
        $example = $this->exampleUpdater->update(
            SchemaNormalizer::normalize($viewSchema['example'])
        );
        if ($example === null) {
            return null;
        }

        $viewSchema['example'] = $example;
        $properties['view'] = new ArrayObject($viewSchema);
        $normalizedItem['properties'] = $properties;

        return new ArrayObject($normalizedItem);
    }
}
