<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Serializer;

/**
 * Recursively cleans OpenAPI data by removing null values and unwanted empty arrays.
 */
final class DataCleaner
{
    public function __construct(
        private readonly ArrayValueProcessor $arrayProcessor,
        private readonly ValueFilter $valueFilter
    ) {
    }

    /**
     * Recursively remove null values and empty arrays from the data.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public function clean(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $processed = $this->processValue($key, $value);
            if ($processed !== null) {
                $result[$key] = $processed;
            }
        }
        return $result;
    }

    /**
     * Process a single value, returning null if it should be filtered out.
     *
     * @return array<array-key, mixed>|string|int|float|bool|null
     */
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
            is_array($value) => $this->arrayProcessor->process(
                $key,
                $value,
                fn (array $data): array => $this->clean($data)
            ),
            default => $value,
        };
    }
}
