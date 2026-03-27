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
        if (! array_key_exists('type', $example)) {
            return null;
        }

        if (array_key_exists('@type', $example)) {
            return null;
        }

        $example['@type'] = $example['type'];
        unset($example['type']);

        return $example;
    }
}
