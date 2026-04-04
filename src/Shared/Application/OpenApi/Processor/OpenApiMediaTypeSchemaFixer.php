<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\MediaType;
use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class OpenApiMediaTypeSchemaFixer
{
    public function __construct(
        private HydraCollectionSchemaFixer $hydraCollectionSchemaFixer
    ) {
    }

    public function fix(MediaType $mediaType): MediaType
    {
        $updatedSchema = $this->updatedSchema($mediaType->getSchema());

        if ($updatedSchema === null) {
            return $mediaType;
        }

        return $mediaType->withSchema(new ArrayObject($updatedSchema));
    }

    /**
     * @param ArrayObject<int|string, SchemaValue>|array<int|string, SchemaValue>|bool|float|int|string|null $schema
     *
     * @return array<int|string, SchemaValue>|null
     */
    private function updatedSchema(
        ArrayObject|array|string|int|float|bool|null $schema
    ): ?array {
        if (! $schema instanceof ArrayObject) {
            return null;
        }

        return $this->hydraCollectionSchemaFixer->fixSchema($schema->getArrayCopy());
    }
}
