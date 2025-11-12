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
     * @param array<array-key, string|int|float|bool|array|null> $parameters
     *
     * @return array<array-key, string|int|float|bool|array|null>
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
        return match (true) {
            !is_array($parameter) => $parameter,
            !isset($parameter['in']) || $parameter['in'] !== 'path' => $parameter,
            default => array_diff_key($parameter, array_flip(self::DISALLOWED_PATH_PROPERTIES)),
        };
    }
}
