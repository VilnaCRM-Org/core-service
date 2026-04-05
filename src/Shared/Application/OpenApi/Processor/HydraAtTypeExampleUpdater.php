<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 */
final class HydraAtTypeExampleUpdater
{
    /**
     * @param array<string, SchemaValue> $example
     *
     * @return array<string, SchemaValue>|null
     */
    public function update(array $example): ?array
    {
        return match (true) {
            ! self::hasKey($example, 'type') => null,
            default => self::withHydraType($example),
        };
    }

    /**
     * @param array<string, SchemaValue> $example
     */
    private static function hasKey(array $example, string $key): bool
    {
        return array_key_exists($key, $example);
    }

    /**
     * @param array<string, SchemaValue> $example
     *
     * @return array<string, SchemaValue>
     */
    private static function withHydraType(array $example): array
    {
        if (! self::hasKey($example, '@type')) {
            $example['@type'] = $example['type'];
        }

        unset($example['type']);

        return $example;
    }
}
