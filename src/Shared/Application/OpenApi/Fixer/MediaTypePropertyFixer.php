<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Fixer;

use ArrayObject;

/**
 * Fixes schema properties for a specific media type definition.
 */
final class MediaTypePropertyFixer
{
    public function __construct(
        private readonly PropertyTypeFixer $propertyTypeFixer
    ) {
    }

    /**
     * @param array{
     *     schema?: array{
     *         properties?: array<string, array<string, string|int|float|bool|array|null>>
     *     }
     * } $mediaTypeObject
     */
    public function process(ArrayObject $content, string $mediaType, array $mediaTypeObject): bool
    {
        $properties = $mediaTypeObject['schema']['properties'] ?? [];

        $arrayProperties = array_filter(
            $properties,
            static fn ($propSchema): bool => is_array($propSchema)
        );

        $wasModified = false;

        foreach ($arrayProperties as $propName => $propSchema) {
            if (!$this->propertyTypeFixer->needsFix($propSchema)) {
                continue;
            }

            $content[$mediaType]['schema']['properties'][$propName] =
                $this->propertyTypeFixer->fix($propSchema);
            $wasModified = true;
        }

        return $wasModified;
    }
}
