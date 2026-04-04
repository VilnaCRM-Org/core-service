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
        if (! ($schema instanceof ArrayObject)) {
            return $mediaType;
        }

        $updatedSchema = $this->hydraCollectionSchemaFixer->fixSchema($schema->getArrayCopy());

        if ($updatedSchema === null) {
            return $mediaType;
        }

        return $mediaType->withSchema(new ArrayObject($updatedSchema));
    }
}
