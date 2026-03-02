<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class SchemaNormalizer
{
    /**
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    public static function normalize(mixed $schema): array
    {
        if ($schema instanceof ArrayObject) {
            return $schema->getArrayCopy();
        }

        return \is_array($schema) ? $schema : [];
    }
}
