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
        $properties = $mediaType['schema']['properties'] ?? null;

        return is_array($properties) ? new self($mediaType, $properties) : null;
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

    /**
     * @param callable(array<string, scalar|array<string, scalar|null>>): array<string, scalar|array<string, scalar|null>>|null $transformer
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    public function transformMediaType(callable $transformer): array
    {
        $properties = $this->transformWith($transformer);

        return $properties === null
            ? $this->mediaType
            : $this->withProperties($properties);
    }
}
