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
        if ($properties === null) {
            return null;
        }

        $updatedProperties = ConstraintViolationPayloadEnricher::enrich($properties);
        if ($updatedProperties === null) {
            return null;
        }

        return ConstraintViolationPropertiesWriter::write(
            $constraintViolation,
            $updatedProperties
        );
    }
}
