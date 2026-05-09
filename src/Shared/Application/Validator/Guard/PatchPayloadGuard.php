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
            return property_exists($payload, $field);
        }

        foreach ($payload as $payloadField => $_) {
            if ($payloadField !== $field) {
                continue;
            }

            return true;
        }

        return false;
    }
}
