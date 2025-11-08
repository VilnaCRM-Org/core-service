<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Serializer;

/**
 * Processes array values during data cleaning.
 */
final class ArrayValueProcessor
{
    public function __construct(
        private readonly ParameterCleaner $parameterCleaner,
        private readonly ValueFilter $valueFilter
    ) {
    }

    /**
     * Process an array value by cleaning parameters and recursively cleaning nested data.
     *
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>|null
     */
    public function process(string|int $key, array $value, callable $recursiveCleaner): ?array
    {
        if ($this->valueFilter->shouldRemove($key, $value)) {
            return null;
        }

        $processedValue = $this->applyParameterCleaning($key, $value);
        $cleanedValue = $recursiveCleaner($processedValue);

        return $this->filterCleanedValue($key, $cleanedValue);
    }

    /**
     * @param array<array-key, mixed> $cleanedValue
     *
     * @return array<array-key, mixed>|null
     */
    private function filterCleanedValue(string|int $key, array $cleanedValue): ?array
    {
        if ($this->valueFilter->shouldRemove($key, $cleanedValue)) {
            return null;
        }

        return $cleanedValue;
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    private function applyParameterCleaning(string|int $key, array $value): array
    {
        if ($key === 'parameters') {
            return $this->parameterCleaner->clean($value);
        }

        return $value;
    }
}
