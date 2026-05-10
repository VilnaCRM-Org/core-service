<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory;

use App\Shared\Application\OpenApi\ValueObject\IriReferenceMediaTypeDefinition;

final readonly class IriReferenceMediaTypeDefinitionFactory
{
    /**
     * @param array<string, scalar|array<string, scalar|null>> $mediaType
     */
    public function create(array $mediaType): ?IriReferenceMediaTypeDefinition
    {
        $properties = $mediaType['schema']['properties'] ?? null;

        /** @psalm-suppress NoValue */
        return is_array($properties)
            ? new IriReferenceMediaTypeDefinition($mediaType, $properties)
            : null;
    }
}
