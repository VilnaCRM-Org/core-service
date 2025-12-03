<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use function is_array;

final class IriReferenceMediaTypeTransformer implements IriReferenceMediaTypeTransformerInterface
{
    private readonly IriReferencePropertyTransformerInterface $propertyTransformer;

    public function __construct(
        ?IriReferencePropertyTransformerInterface $propertyTransformer = null
    ) {
        $this->propertyTransformer = $propertyTransformer ?? new IriReferencePropertyTransformer();
    }

    /**
     * @param array<string, scalar|array<string, scalar|null>> $mediaType
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    public function transform(array $mediaType): array
    {
        $definition = IriReferenceMediaTypeDefinition::from($mediaType);

        return $definition?->transformMediaType($this->transformProperty(...))
            ?? $mediaType;
    }

    private function transformProperty(
        array|string|int|float|bool|null $schema
    ): array|string|int|float|bool|null {
        return is_array($schema)
            ? $this->propertyTransformer->transform($schema)
            : $schema;
    }
}
