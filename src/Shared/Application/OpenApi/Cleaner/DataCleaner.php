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
        $keys = array_keys($data);

        $values = array_map(
            fn (
                array|\ArrayObject|string|int|float|bool|null $value,
                string|int $key
            ): array|string|int|float|bool|null => $this->cleanValue($key, $value),
            $data,
            $keys
        );

        return array_filter(
            array_combine($keys, $values) ?? [],
            static fn ($value): bool => $value !== null
        );
    }

    /**
     * @return array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null
     */
    private function cleanValue(
        string|int $key,
        array|\ArrayObject|string|int|float|bool|null $value
    ): array|string|int|float|bool|null {
        $normalized = $this->normalize($value);

        return $this->valueFilter->shouldRemove($key, $normalized)
            ? null
            : $this->cleanNormalizedValue($key, $normalized);
    }

    private function normalize(
        array|\ArrayObject|string|int|float|bool|null $value
    ): array|string|int|float|bool|null {
        return $value instanceof \ArrayObject ? $value->getArrayCopy() : $value;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $value
     *
     * @return array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null
     */
    private function cleanNormalizedValue(
        string|int $key,
        array|string|int|float|bool|null $value
    ): array|string|int|float|bool|null {
        return is_array($value)
            ? $this->arrayProcessor->clean(
                $key,
                $value,
                fn (array $nested): array => $this->clean($nested)
            )
            : $value;
    }
}
