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
        $payload = self::extractPayload($properties);
        if ($payload === null) {
            return null;
        }

        $properties['payload'] = self::payloadWithItems($payload);
        $constraintViolation['properties']['violations']['items']['properties'] = $properties;

        return $constraintViolation;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $constraintViolation
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    private static function extractProperties(array $constraintViolation): array
    {
        return SchemaNormalizer::normalize(
            $constraintViolation['properties']['violations']['items']['properties'] ?? null
        );
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $properties
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>|null
     */
    private static function extractPayload(array $properties): ?array
    {
        $payload = SchemaNormalizer::normalize($properties['payload'] ?? null);
        $payloadTypes = (array) ($payload['type'] ?? []);
        $isArrayPayload = in_array('array', $payloadTypes, true);

        if ($isArrayPayload && array_key_exists('items', $payload) && $payload['items'] === null) {
            unset($payload['items']);
        }

        return PayloadItemsRequirementChecker::shouldAddItems($payload) ? $payload : null;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $payload
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    private static function payloadWithItems(array $payload): array
    {
        $payload['items'] = ['type' => 'object'];

        return $payload;
    }
}
