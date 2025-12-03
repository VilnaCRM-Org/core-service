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
    public function fix(ArrayObject $content, string $mediaType, array $mediaTypeObject): bool
    {
        $properties = $mediaTypeObject['schema']['properties'] ?? [];

        $propertiesToFix = array_filter(
            $properties,
            fn ($propSchema): bool => is_array($propSchema)
                && $this->propertyTypeFixer->needsFix($propSchema)
        );

        array_walk(
            $propertiesToFix,
            function (array $propSchema, string $propName) use ($content, $mediaType): void {
                $content[$mediaType]['schema']['properties'][$propName] = $this
                    ->propertyTypeFixer
                    ->fix($propSchema);
            }
        );

        return $propertiesToFix !== [];
    }
}
