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
    public function update(array $constraintViolation): ?array
    {
        $properties = (new ConstraintViolationPropertiesExtractor())->extract($constraintViolation);
        $updatedProperties = $this->updatedProperties($properties);

        return match (true) {
            $properties === null => null,
            $updatedProperties === null => null,
            default => (new ConstraintViolationPropertiesWriter())->write(
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
    private function updatedProperties(?array $properties): ?array
    {
        return match ($properties) {
            null => null,
            default => (new ConstraintViolationPayloadEnricher())->enrich($properties),
        };
    }
}
