<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Writer\ConstraintViolationPropertiesWriter;
use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class ConstraintViolationPayloadItemsUpdater
{
    /**
     * @param array<string, SchemaValue> $constraintViolation
     *
     * @return array<string, SchemaValue>|null
     */
    public static function update(array $constraintViolation): ?array
    {
        $properties = ConstraintViolationPropertiesExtractor::extract($constraintViolation);
        $updatedProperties = self::updatedProperties($properties);

        return match (true) {
            $properties === null => null,
            $updatedProperties === null => null,
            default => ConstraintViolationPropertiesWriter::write(
                $constraintViolation,
                $updatedProperties
            ),
        };
    }

    /**
     * @param array<string, SchemaValue> $properties
     *
     * @return array<string, SchemaValue>|null
     */
    private static function updatedProperties(?array $properties): ?array
    {
        return match ($properties) {
            null => null,
            default => ConstraintViolationPayloadEnricher::enrich($properties),
        };
    }
}
