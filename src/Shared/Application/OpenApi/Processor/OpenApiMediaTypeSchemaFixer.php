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
        $updatedSchema = match (true) {
            $schema instanceof ArrayObject => $this->hydraCollectionSchemaFixer->fixSchema(
                $schema->getArrayCopy()
            ),
            default => null,
        };

        return match (true) {
            ! ($schema instanceof ArrayObject) => $mediaType,
            $updatedSchema === null => $mediaType,
            default => $mediaType->withSchema(new ArrayObject($updatedSchema)),
        };
    }
}
