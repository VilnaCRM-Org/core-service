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
        $cleaned = [];

        foreach ($data as $key => $value) {
            $processedValue = $this->processValue($key, $value);

            if ($processedValue !== null) {
                $cleaned[$key] = $processedValue;
            }
        }

        return $cleaned;
    }

    /**
     * Process a single value, returning null if it should be filtered out.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function processValue(
        string|int $key,
        array|string|int|float|bool|null $value
    ): array|string|int|float|bool|null {
        if ($this->valueFilter->shouldRemove($key, $value)) {
            return null;
        }

        return $this->processArrayOrValue($key, $value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function processArrayOrValue(
        string|int $key,
        array|string|int|float|bool|null $value
    ): array|string|int|float|bool|null {
        if (!is_array($value)) {
            return $value;
        }

        return $this->arrayProcessor->process(
            $key,
            $value,
            fn (array $data): array => $this->clean($data)
        );
    }
}
