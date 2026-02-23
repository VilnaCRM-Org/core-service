<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class HydraAllOfItemUpdater
{
    public function __construct(
        private HydraAtTypeExampleUpdater $exampleUpdater
    ) {
    }

    public function update(ArrayObject|array $item): ?ArrayObject
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

        $example = $this->exampleUpdater->update(
            SchemaNormalizer::normalize($viewSchema['example'] ?? null)
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
