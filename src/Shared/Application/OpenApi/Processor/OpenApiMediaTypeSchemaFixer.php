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
        $schema = $mediaType->getSchema();

        return $this->fixedMediaType($mediaType, $schema);
    }

    /**
     * @param ArrayObject<int|string, SchemaValue>|null $schema
     *
     * @return ArrayObject<int|string, SchemaValue>|null
     */
    private function updatedSchema(?ArrayObject $schema): ?ArrayObject
    {
        return match ($schema) {
            null => null,
            default => $this->fixedSchema($schema),
        };
    }

    private function fixedMediaType(
        MediaType $mediaType,
        ?ArrayObject $schema
    ): MediaType {
        $updatedSchema = $this->updatedSchema($schema);

        return match ($updatedSchema === $schema) {
            true => $mediaType,
            default => $mediaType->withSchema($updatedSchema),
        };
    }

    /**
     * @param ArrayObject<int|string, SchemaValue> $schema
     */
    private function fixedSchema(ArrayObject $schema): ArrayObject
    {
        $updatedSchema = $this->hydraCollectionSchemaFixer->fixSchema($schema->getArrayCopy());

        return match ($updatedSchema) {
            null => $schema,
            default => new ArrayObject($updatedSchema),
        };
    }
}
