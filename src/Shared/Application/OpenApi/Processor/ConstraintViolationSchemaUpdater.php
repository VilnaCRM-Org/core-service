<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 */
final class ConstraintViolationSchemaUpdater
{
    private const SCHEMA_KEY_PREFIX = 'ConstraintViolation';

    /**
     * @param ArrayObject<string, SchemaValue> $schemas
     */
    public function update(ArrayObject $schemas): ?ArrayObject
    {
        $updatedSchemas = $schemas->getArrayCopy();
        $hasChanges = false;

        foreach ($this->matchingSchemas($updatedSchemas) as $schemaName => $schema) {
            $updatedSchema = $this->updatedConstraintViolationSchema($schema);
            if ($updatedSchema === null) {
                continue;
            }

            $updatedSchemas[$schemaName] = $this->wrappedUpdatedSchema($updatedSchema);
            $hasChanges = true;
        }

        return match ($hasChanges) {
            true => new ArrayObject($updatedSchemas),
            default => null,
        };
    }

    /**
     * @param array<string, SchemaValue> $schemas
     *
     * @return array<string, SchemaValue>
     */
    private function matchingSchemas(array $schemas): array
    {
        return array_filter(
            $schemas,
            static fn (string $schemaName): bool => str_starts_with(
                $schemaName,
                self::SCHEMA_KEY_PREFIX
            ),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param SchemaValue $schema
     *
     * @return array<string, SchemaValue>|null
     */
    private function updatedConstraintViolationSchema(
        object|array|bool|float|int|string|null $schema
    ): ?array {
        return ConstraintViolationPayloadItemsUpdater::update(
            SchemaNormalizer::normalize($schema)
        );
    }

    /**
     * @param array<string, SchemaValue> $schema
     */
    private function wrappedUpdatedSchema(array $schema): ArrayObject
    {
        return new ArrayObject($schema);
    }
}
