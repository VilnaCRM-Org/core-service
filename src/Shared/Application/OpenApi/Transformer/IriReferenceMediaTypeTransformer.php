<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use App\Shared\Application\OpenApi\Factory\IriReferenceMediaTypeDefinitionFactory;

use function is_array;

final class IriReferenceMediaTypeTransformer implements IriReferenceMediaTypeTransformerInterface
{
    public function __construct(
        private readonly IriReferencePropertyTransformerInterface $propertyTransformer,
        private readonly IriReferenceMediaTypeDefinitionFactory $definitionFactory
    ) {
    }

    /**
     * @param array<string, scalar|array<string, scalar|null>> $mediaType
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    public function transform(array $mediaType): array
    {
        $definition = $this->definitionFactory->create($mediaType);

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
