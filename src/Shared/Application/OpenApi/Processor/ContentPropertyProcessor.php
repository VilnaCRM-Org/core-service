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
        private readonly MediaTypePropertyProcessor $mediaTypeProcessor
    ) {
    }

    public function process(ArrayObject $content): bool
    {
        $mediaTypes = array_filter(
            iterator_to_array($content),
            static fn ($mediaTypeObject): bool => is_array($mediaTypeObject)
        );

        $wasModified = false;

        foreach ($mediaTypes as $mediaType => $mediaTypeObject) {
            $wasModified = $this->mediaTypeProcessor->process(
                $content,
                (string) $mediaType,
                $mediaTypeObject
            ) || $wasModified;
        }

        return $wasModified;
    }
}
