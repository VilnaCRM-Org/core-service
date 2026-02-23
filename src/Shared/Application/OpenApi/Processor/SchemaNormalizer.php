<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class SchemaNormalizer
{
    /**
     * @param ArrayObject|array<string, array|bool|float|int|string|ArrayObject|null>|null $schema
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    public static function normalize(ArrayObject|array|null $schema): array
    {
        return $schema instanceof ArrayObject
            ? $schema->getArrayCopy()
            : (array) $schema;
    }
}
