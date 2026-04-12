<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 */
final class NullableSchemaTypeNormalizer
{
    /**
     * @param array<int|string, SchemaValue> $property
     *
     * @return array<int|string, SchemaValue>|null
     */
    public function normalize(array $property): ?array
    {
        $type = $property['type'] ?? null;

        if (! \is_array($type) || ! \in_array('null', $type, true)) {
            return null;
        }

        $nonNullableTypes = array_filter(
            $type,
            static fn (
                array|bool|float|int|object|string|null $candidate
            ): bool => \is_string($candidate) && $candidate !== 'null'
        );

        if (\count($nonNullableTypes) !== 1) {
            return null;
        }

        $property['type'] = current($nonNullableTypes);

        return $property;
    }
}
