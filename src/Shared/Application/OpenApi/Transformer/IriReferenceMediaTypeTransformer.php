<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use App\Shared\Application\OpenApi\ValueObject\IriReferenceMediaTypeDefinition;

use function is_array;

final class IriReferenceMediaTypeTransformer implements IriReferenceMediaTypeTransformerInterface
{
    public function __construct(
        private readonly IriReferencePropertyTransformerInterface $propertyTransformer
    ) {
    }

    /**
     * @param array<string, scalar|array<string, scalar|null>> $mediaType
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    #[\Override]
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
