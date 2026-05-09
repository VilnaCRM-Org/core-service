<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Guard;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @psalm-type PatchPayloadValue = object|iterable|string|int|float|bool|null
 */
final class PatchPayloadGuard
{
    public const EMPTY_PAYLOAD_MESSAGE = 'PATCH payload must contain at least one supported field.';

    /**
     * @param object|iterable<array-key, PatchPayloadValue> $payload
     * @param iterable<non-empty-string> $fields
     */
    public function assertContainsAnyField(
        object|iterable $payload,
        iterable $fields
    ): void {
        foreach ($fields as $field) {
            if ($this->containsField($payload, $field)) {
                return;
            }
        }

        throw new BadRequestHttpException(self::EMPTY_PAYLOAD_MESSAGE);
    }

    /**
     * @param object|iterable<array-key, PatchPayloadValue> $payload
     */
    private function containsField(object|iterable $payload, string $field): bool
    {
        if (is_object($payload)) {
            return $this->objectContainsMeaningfulField($payload, $field);
        }

        foreach ($payload as $payloadField => $value) {
            if ($payloadField !== $field) {
                continue;
            }

            return $this->hasMeaningfulValue($value);
        }

        return false;
    }

    private function objectContainsMeaningfulField(object $payload, string $field): bool
    {
        if (! property_exists($payload, $field)) {
            return false;
        }

        foreach ((array) $payload as $property => $value) {
            if ($this->objectPropertyName($property) !== $field) {
                continue;
            }

            return $this->hasMeaningfulValue($value);
        }

        return false;
    }

    private function objectPropertyName(string $property): string
    {
        $visibilityMarkerPosition = strrpos($property, "\0");
        if ($visibilityMarkerPosition === false) {
            return $property;
        }

        return substr($property, $visibilityMarkerPosition + 1);
    }

    /**
     * @param PatchPayloadValue $value
     */
    private function hasMeaningfulValue(
        object|iterable|string|int|float|bool|null $value
    ): bool {
        if ($value === null) {
            return false;
        }

        return ! is_string($value) || trim($value) !== '';
    }
}
