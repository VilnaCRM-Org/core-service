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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function update(array $constraintViolation): ?array
    {
        if (! self::hasViolationItemProperties($constraintViolation)) {
            return null;
        }

        $properties = self::extractProperties($constraintViolation);
        $payload = self::extractPayload($properties);
        if ($payload === null) {
            return null;
        }

        $properties += ['code' => self::defaultCodeProperty()];
        $properties['payload'] = self::payloadWithItems($payload);
        $rootProperties = SchemaNormalizer::normalize($constraintViolation['properties'] ?? null);
        $violations = SchemaNormalizer::normalize($rootProperties['violations'] ?? null);
        $items = SchemaNormalizer::normalize($violations['items'] ?? null);
        $items['properties'] = $properties;
        $violations['items'] = $items;
        $rootProperties['violations'] = $violations;
        $constraintViolation['properties'] = $rootProperties;

        return $constraintViolation;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $constraintViolation
     */
    private static function hasViolationItemProperties(array $constraintViolation): bool
    {
        $properties = $constraintViolation['properties'] ?? null;
        if (! is_array($properties) && ! $properties instanceof ArrayObject) {
            return false;
        }

        $normalizedProperties = SchemaNormalizer::normalize($properties);
        $violations = $normalizedProperties['violations'] ?? null;
        if (! is_array($violations) && ! $violations instanceof ArrayObject) {
            return false;
        }

        $normalizedViolations = SchemaNormalizer::normalize($violations);
        $items = $normalizedViolations['items'] ?? null;
        if (! is_array($items) && ! $items instanceof ArrayObject) {
            return false;
        }

        $normalizedItems = SchemaNormalizer::normalize($items);
        $itemProperties = $normalizedItems['properties'] ?? null;

        return is_array($itemProperties) || $itemProperties instanceof ArrayObject;
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
        if (! array_key_exists('payload', $properties)) {
            return ['type' => 'array'];
        }

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
