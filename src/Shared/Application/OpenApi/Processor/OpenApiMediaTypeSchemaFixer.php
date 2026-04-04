<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\MediaType;
use ArrayObject;

final class OpenApiMediaTypeSchemaFixer
{
    public function __construct(
        private HydraCollectionSchemaFixer $hydraCollectionSchemaFixer
    ) {
    }

    public function fix(MediaType $mediaType): MediaType
    {
        $schema = $mediaType->getSchema();
        $normalizedSchema = $schema?->getArrayCopy() ?? [];
        $updatedSchema = $schema === null
            ? null
            : $this->hydraCollectionSchemaFixer->fixSchema($normalizedSchema)
        ;

        return $updatedSchema === null
            ? $mediaType
            : $mediaType->withSchema(new ArrayObject($updatedSchema));
    }
}
