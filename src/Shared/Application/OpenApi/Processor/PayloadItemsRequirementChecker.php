<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class PayloadItemsRequirementChecker
{
    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $payload
     */
    public static function shouldAddItems(array $payload): bool
    {
        $payload += ['type' => null];

        return $payload['type'] === 'array'
            && ! array_key_exists('items', $payload);
    }
}
