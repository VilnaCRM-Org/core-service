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
     * @param array<mixed> $value
     *
     * @return array<mixed>|null
     */
    public function process(string|int $key, array $value, callable $recursiveCleaner): ?array
    {
        if ($this->valueFilter->shouldRemove($key, $value)) {
            return null;
        }

        $processedValue = $this->applyParameterCleaning($key, $value);
        $cleanedValue = $recursiveCleaner($processedValue);

        return $this->valueFilter->shouldRemove($key, $cleanedValue) ? null : $cleanedValue;
    }

    /**
     * @param array<mixed> $value
     *
     * @return array<mixed>
     */
    private function applyParameterCleaning(string|int $key, array $value): array
    {
        return $key === 'parameters' ? $this->parameterCleaner->clean($value) : $value;
    }
}
