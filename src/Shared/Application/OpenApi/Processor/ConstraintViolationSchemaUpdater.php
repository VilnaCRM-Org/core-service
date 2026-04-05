<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
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
            $updatedSchema = ConstraintViolationPayloadItemsUpdater::update(
                SchemaNormalizer::normalize($schema)
            );
            if ($updatedSchema === null) {
                continue;
            }

            $updatedSchemas[$schemaName] = new ArrayObject($updatedSchema);
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
}
