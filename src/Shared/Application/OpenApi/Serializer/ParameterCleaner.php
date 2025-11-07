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
     * @param array<mixed> $parameters
     *
     * @return array<mixed>
     */
    public function clean(array $parameters): array
    {
        return array_map(
            fn (array|string|int|float|bool|null $parameter): array|string|int|float|bool|null => $this->cleanParameter($parameter),
            $parameters
        );
    }

    /**
     * @param array<mixed>|string|int|float|bool|null $parameter
     *
     * @return array<mixed>|string|int|float|bool|null
     */
    private function cleanParameter(array|string|int|float|bool|null $parameter): array|string|int|float|bool|null
    {
        if (!is_array($parameter) || !$this->isPathParameter($parameter)) {
            return $parameter;
        }

        return $this->removeDisallowedProperties($parameter);
    }

    /**
     * @param array<mixed> $parameter
     */
    private function isPathParameter(array $parameter): bool
    {
        return isset($parameter['in']) && $parameter['in'] === 'path';
    }

    /**
     * @param array<mixed> $parameter
     *
     * @return array<mixed>
     */
    private function removeDisallowedProperties(array $parameter): array
    {
        foreach (self::DISALLOWED_PATH_PROPERTIES as $property) {
            unset($parameter[$property]);
        }

        return $parameter;
    }
}
