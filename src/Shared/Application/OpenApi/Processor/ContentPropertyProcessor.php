<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * Processes content properties to fix IRI reference types using functional approach.
 */
final class ContentPropertyProcessor
{
    public function __construct(
        private readonly PropertyTypeFixer $propertyTypeFixer
    ) {
    }

    public function process(ArrayObject $content): bool
    {
        return array_reduce(
            iterator_to_array($content),
            fn (bool $modified, array $mediaTypeObject) => $this->processMediaType(
                $content,
                array_search($mediaTypeObject, iterator_to_array($content), true),
                $mediaTypeObject
            ) || $modified,
            false
        );
    }

    /**
     * @param array<string, mixed> $mediaTypeObject
     */
    private function processMediaType(
        ArrayObject $content,
        string|int|false $mediaType,
        array $mediaTypeObject
    ): bool {
        $properties = $mediaTypeObject['schema']['properties'] ?? [];

        return array_reduce(
            array_keys($properties),
            fn (bool $wasModified, string $propName) => $this->fixPropertyIfNeeded(
                $content,
                (string) $mediaType,
                $propName,
                $properties[$propName]
            ) || $wasModified,
            false
        );
    }

    /**
     * @param array<string, mixed> $propSchema
     */
    private function fixPropertyIfNeeded(
        ArrayObject $content,
        string $mediaType,
        string $propName,
        array $propSchema
    ): bool {
        if (!$this->propertyTypeFixer->needsFix($propSchema)) {
            return false;
        }

        $content[$mediaType]['schema']['properties'][$propName] =
            $this->propertyTypeFixer->fix($propSchema);

        return true;
    }
}
