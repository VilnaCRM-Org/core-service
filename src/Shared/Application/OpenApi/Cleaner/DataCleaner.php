<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

/**
 * Recursively cleans OpenAPI data by removing null values and unwanted empty arrays.
 */
final class DataCleaner
{
    public function __construct(
        private readonly ArrayValueCleaner $arrayProcessor,
        private readonly ValueFilter $valueFilter
    ) {
    }

    /**
     * Recursively remove null values and empty arrays from the data.
     *
     * @param array<array-key, array|\ArrayObject|string|int|float|bool|null> $data
     *
     * @return array<array-key, array|string|int|float|bool|null>
     */
    public function clean(array $data): array
    {
        return array_reduce(
            array_keys($data),
            function (array $result, string|int $key) use ($data): array {
                $processed = $this->processValue($key, $data[$key]);
                return $processed !== null ? array_merge($result, [$key => $processed]) : $result;
            },
            []
        );
    }

    private function processValue(
        string|int $key,
        array|\ArrayObject|string|int|float|bool|null $value
    ): array|string|int|float|bool|null {
        //Convert ArrayObject to array
        if ($value instanceof \ArrayObject) {
            $value = $value->getArrayCopy();
        }

        return match (true) {
            $this->valueFilter->shouldRemove($key, $value) => null,
            is_array($value) => $this->arrayProcessor->clean(
                $key,
                $value,
                fn (array $data): array => $this->clean($data)
            ),
            default => $value,
        };
    }
}
