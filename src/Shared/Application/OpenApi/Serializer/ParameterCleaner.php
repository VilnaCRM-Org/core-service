<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Serializer;

/**
 * Cleans OpenAPI parameters by removing disallowed properties from path parameters.
 */
final class ParameterCleaner
{
    private const DISALLOWED_PATH_PROPERTIES = ['allowEmptyValue', 'allowReserved'];

    /**
     * Clean parameters array by removing invalid properties from path parameters.
     *
     * @param array<array-key, mixed> $parameters
     *
     * @return array<array-key, mixed>
     */
    public function clean(array $parameters): array
    {
        $cleaner = fn (
            array|string|int|float|bool|null $parameter
        ): array|string|int|float|bool|null => $this->cleanParameter($parameter);

        return array_map($cleaner, $parameters);
    }

    private function cleanParameter(
        array|string|int|float|bool|null $parameter
    ): array|string|int|float|bool|null {
        if (!$this->shouldCleanParameter($parameter)) {
            return $parameter;
        }

        return $this->removeDisallowedProperties($parameter);
    }

    private function shouldCleanParameter(array|string|int|float|bool|null $parameter): bool
    {
        if (!is_array($parameter)) {
            return false;
        }

        return $this->isPathParameter($parameter);
    }

    /**
     * @param array<array-key, mixed> $parameter
     */
    private function isPathParameter(array $parameter): bool
    {
        if (!isset($parameter['in'])) {
            return false;
        }

        return $parameter['in'] === 'path';
    }

    /**
     * @param array<array-key, mixed> $parameter
     *
     * @return array<array-key, mixed>
     */
    private function removeDisallowedProperties(array $parameter): array
    {
        foreach (self::DISALLOWED_PATH_PROPERTIES as $property) {
            unset($parameter[$property]);
        }

        return $parameter;
    }
}
