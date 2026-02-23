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
        $properties = self::extractProperties($constraintViolation);
        if ($properties === null) {
            return null;
        }

        $payload = SchemaNormalizer::normalize($properties['payload'] ?? null);
        if (!PayloadItemsRequirementChecker::shouldAddItems($payload)) {
            return null;
        }

        $properties['payload'] = self::payloadWithItems($payload);
        $constraintViolation['properties']['violations']['items']['properties'] = $properties;

        return $constraintViolation;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $constraintViolation
     *
     * @return array<string, mixed>|null
     */
    private static function extractProperties(array $constraintViolation): ?array
    {
        $properties = SchemaNormalizer::normalize(
            $constraintViolation['properties']['violations']['items']['properties'] ?? null
        );

        return $properties === [] ? null : $properties;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private static function payloadWithItems(array $payload): array
    {
        $payload['items'] = ['type' => 'object'];

        return $payload;
    }
}
