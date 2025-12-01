<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use function is_array;

final class IriReferenceMediaTypeTransformer
{
    private IriReferencePropertyTransformer $propertyTransformer;

    public function __construct(?IriReferencePropertyTransformer $propertyTransformer = null)
    {
        $this->propertyTransformer = $propertyTransformer ?? new IriReferencePropertyTransformer();
    }

    /**
     * @param array<string, scalar|array<string, scalar|null>> $mediaType
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    public function transform(array $mediaType): array
    {
        $schema = $mediaType['schema'] ?? null;
        $properties = $schema['properties'] ?? null;

        if (!is_array($schema) || !is_array($properties)) {
            return $mediaType;
        }

        $transformedProperties = array_map(
            fn ($candidate) => $this->transformProperty($candidate),
            $properties
        );

        if ($transformedProperties === $properties) {
            return $mediaType;
        }

        $mediaType['schema']['properties'] = $transformedProperties;

        return $mediaType;
    }

    private function transformProperty(mixed $schema): mixed
    {
        return is_array($schema)
            ? $this->propertyTransformer->transform($schema)
            : $schema;
    }
}
