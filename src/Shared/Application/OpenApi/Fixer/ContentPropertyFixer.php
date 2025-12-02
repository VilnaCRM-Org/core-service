<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Fixer;

use ArrayObject;

/**
 * Fixes content properties to correct IRI reference types using functional approach.
 */
final class ContentPropertyFixer
{
    public function __construct(
        private readonly MediaTypePropertyFixer $mediaTypePropertyFixer
    ) {
    }

    public function fix(ArrayObject $content): bool
    {
        $mediaTypes = array_filter(
            iterator_to_array($content),
            static fn ($mediaTypeObject): bool => is_array($mediaTypeObject)
        );

        return array_reduce(
            array_keys($mediaTypes),
            fn (bool $modified, string $mediaType): bool => $this->mediaTypePropertyFixer->fix($content, $mediaType, $mediaTypes[$mediaType])
                || $modified,
            false
        );
    }
}
