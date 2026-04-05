<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class ConstraintViolationPayloadEnricher
{
    /**
     * @param array<string, SchemaValue> $properties
     *
     * @return array<string, SchemaValue>|null
     */
    public static function enrich(array $properties): ?array
    {
        $payload = ConstraintViolationPayloadBuilder::build($properties);
        if ($payload === null) {
            return null;
        }

        $properties += ['code' => self::defaultCodeProperty()];
        $properties['payload'] = $payload;

        return $properties;
    }

    /**
     * @return array<string, string>
     */
    private static function defaultCodeProperty(): array
    {
        return [
            'type' => 'string',
            'description' => 'The machine-readable violation code',
        ];
    }
}
