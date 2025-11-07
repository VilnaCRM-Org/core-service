<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * Processes content properties to fix IRI reference types.
 */
final class ContentPropertyProcessor
{
    public function __construct(
        private readonly PropertyTypeFixer $propertyTypeFixer
    ) {
    }

    public function process(ArrayObject $content): bool
    {
        $modified = false;

        foreach ($content as $mediaType => $mediaTypeObject) {
            if ($this->processMediaType($content, $mediaType, $mediaTypeObject)) {
                $modified = true;
            }
        }

        return $modified;
    }

    /**
     * @param array<string, mixed> $mediaTypeObject
     */
    private function processMediaType(
        ArrayObject $content,
        string $mediaType,
        array $mediaTypeObject
    ): bool {
        $properties = $mediaTypeObject['schema']['properties'] ?? [];
        $modified = false;

        foreach ($properties as $propName => $propSchema) {
            if ($this->propertyTypeFixer->needsFix($propSchema)) {
                $content[$mediaType]['schema']['properties'][$propName] =
                    $this->propertyTypeFixer->fix($propSchema);
                $modified = true;
            }
        }

        return $modified;
    }
}
