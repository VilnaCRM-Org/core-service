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
     * @param iterable<non-empty-string> $blankStringFields
     */
    public function assertContainsAnyField(
        object|iterable $payload,
        iterable $fields,
        iterable $blankStringFields = []
    ): void {
        $blankStringFieldMap = array_fill_keys(
            iterator_to_array($blankStringFields),
            true
        );

        foreach ($fields as $field) {
            if ($this->containsField($payload, $field, $blankStringFieldMap)) {
                return;
            }
        }

        throw new BadRequestHttpException(self::EMPTY_PAYLOAD_MESSAGE);
    }

    /**
     * @param object|iterable<array-key, PatchPayloadValue> $payload
     * @param array<string, true> $blankStringFieldMap
     */
    private function containsField(
        object|iterable $payload,
        string $field,
        array $blankStringFieldMap
    ): bool {
        if (is_object($payload)) {
            return $this->objectContainsMeaningfulField(
                $payload,
                $field,
                $blankStringFieldMap
            );
        }

        foreach ($payload as $payloadField => $value) {
            if ($payloadField !== $field) {
                continue;
            }

            return $this->hasMeaningfulValue(
                $value,
                isset($blankStringFieldMap[$field])
            );
        }

        return false;
    }

    /**
     * @param array<string, true> $blankStringFieldMap
     */
    private function objectContainsMeaningfulField(
        object $payload,
        string $field,
        array $blankStringFieldMap
    ): bool {
        foreach ((array) $payload as $property => $value) {
            if ($this->objectPropertyName($property) !== $field) {
                continue;
            }

            return $this->hasMeaningfulValue(
                $value,
                isset($blankStringFieldMap[$field])
            );
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
        object|iterable|string|int|float|bool|null $value,
        bool $allowBlankString
    ): bool {
        if ($value === null) {
            return false;
        }

        return ! is_string($value)
            || $allowBlankString
            || trim($value) !== '';
    }
}
