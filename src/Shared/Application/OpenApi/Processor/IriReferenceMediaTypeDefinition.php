<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use function array_map;
use function is_array;

/**
 * @internal Extracts and mutates media type definitions without increasing transformer complexity.
 */
final class IriReferenceMediaTypeDefinition
{
    /**
     * @param array<string, scalar|array<string, scalar|null>> $mediaType
     * @param array<string, scalar|array<string, scalar|null>> $properties
     */
    private function __construct(
        private readonly array $mediaType,
        private readonly array $properties
    ) {
    }

    /**
     * @param array<string, scalar|array<string, scalar|null>> $mediaType
     */
    public static function from(array $mediaType): ?self
    {
        $schema = $mediaType['schema'] ?? null;
        $properties = $schema['properties'] ?? null;

        if (!is_array($schema) || !is_array($properties)) {
            return null;
        }

        return new self($mediaType, $properties);
    }

    /**
     * @return array<string, scalar|array<string, scalar|null>>|null
     */
    public function transformWith(callable $transformer): ?array
    {
        $transformed = array_map($transformer, $this->properties);

        return $transformed === $this->properties ? null : $transformed;
    }

    /**
     * @param array<string, scalar|array<string, scalar|null>> $properties
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    public function withProperties(array $properties): array
    {
        $mediaType = $this->mediaType;
        $mediaType['schema']['properties'] = $properties;

        return $mediaType;
    }
}
