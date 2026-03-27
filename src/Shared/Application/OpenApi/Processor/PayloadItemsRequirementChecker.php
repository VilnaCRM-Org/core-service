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
        $type = $payload['type'] ?? [];
        $types = \is_string($type) ? [$type] : SchemaNormalizer::normalize($type);

        return in_array('array', $types, true)
            && (($payload['items'] ?? null) === null);
    }
}
