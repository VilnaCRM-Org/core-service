<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

/**
 * Cleans array values during data cleaning.
 */
final class ArrayValueCleaner
{
    public function __construct(
        private readonly ParameterCleaner $parameterCleaner,
        private readonly ValueFilter $valueFilter
    ) {
    }

    /**
     * Clean an array value by cleaning parameters and recursively cleaning nested data.
     *
     * @param array<array-key, string|int|float|bool|array|null> $value
     *
     * @return array<array-key, string|int|float|bool|array|null>|null
     */
    public function clean(string|int $key, array $value, callable $recursiveCleaner): ?array
    {
        return $this->valueFilter->shouldRemove($key, $value)
            ? null
            : $this->processCleanableValue($key, $value, $recursiveCleaner);
    }

    /**
     * @param array<array-key, string|int|float|bool|array|null> $cleanedValue
     *
     * @return array<array-key, string|int|float|bool|array|null>|null
     */
    private function filterCleanedValue(string|int $key, array $cleanedValue): ?array
    {
        return $this->valueFilter->shouldRemove($key, $cleanedValue)
            ? null
            : $cleanedValue;
    }

    /**
     * @param array<array-key, string|int|float|bool|array|null> $value
     *
     * @return array<array-key, string|int|float|bool|array|null>
     */
    private function applyParameterCleaning(string|int $key, array $value): array
    {
        return $key === 'parameters'
            ? $this->parameterCleaner->clean($value)
            : $value;
    }

    /**
     * @param array<array-key, string|int|float|bool|array|null> $value
     *
     * @return array<array-key, string|int|float|bool|array|null>|null
     */
    private function processCleanableValue(
        string|int $key,
        array $value,
        callable $recursiveCleaner
    ): ?array {
        $processedValue = $this->applyParameterCleaning($key, $value);
        $cleanedValue = $recursiveCleaner($processedValue);

        return $this->filterCleanedValue($key, $cleanedValue);
    }
}
