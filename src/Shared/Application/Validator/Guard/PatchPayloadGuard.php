<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Guard;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class PatchPayloadGuard
{
    public const EMPTY_PAYLOAD_MESSAGE = 'PATCH payload must contain at least one supported field.';

    /**
     * @param list<non-empty-string> $fields
     */
    public static function assertContainsAnyField(
        object $payload,
        array $fields
    ): void {
        foreach ($fields as $field) {
            if (!property_exists($payload, $field)) {
                continue;
            }

            $value = $payload->{$field};
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }

            return;
        }

        throw new BadRequestHttpException(self::EMPTY_PAYLOAD_MESSAGE);
    }
}
