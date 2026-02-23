<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class ConstraintViolationPayloadItemsUpdater
{
    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $constraintViolation
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>|null
     */
    public static function update(array $constraintViolation): ?array
    {
        $properties = $constraintViolation['properties']['violations']['items']['properties']
            ?? null;
        if (!is_array($properties)) {
            return null;
        }

        $payload = SchemaNormalizer::normalize($properties['payload'] ?? null);
        if (!PayloadItemsRequirementChecker::shouldAddItems($payload)) {
            return null;
        }

        $payload['items'] = ['type' => 'object'];
        $properties['payload'] = $payload;
        $constraintViolation['properties']['violations']['items']['properties'] = $properties;

        return $constraintViolation;
    }
}
