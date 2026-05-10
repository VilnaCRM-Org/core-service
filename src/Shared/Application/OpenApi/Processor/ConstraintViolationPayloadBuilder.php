<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class ConstraintViolationPayloadBuilder
{
    /**
     * @param array<string, SchemaValue> $properties
     *
     * @return array<string, SchemaValue>|null
     */
    public function build(array $properties): ?array
    {
        $payload = (new ConstraintViolationPayloadItemsCleaner())->clean(
            (new SchemaNormalizer())->normalize($properties['payload'] ?? ['type' => 'array'])
        );

        return (new PayloadItemsRequirementChecker())->shouldAddItems($payload)
            ? ['items' => ['type' => 'object']] + $payload
            : null;
    }
}
