<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Applier;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

use function is_array;

final class SpecExtensionPropertyApplier
{
    /**
     * @param array<string, string|int|float|bool|array|null>|ArrayObject<string, string|int|float|bool|array|null>|null $extensionProperties
     */
    public function apply(array|ArrayObject|null $extensionProperties, OpenApi $openApi): OpenApi
    {
        $normalizedExtensionProperties = $this->normalize($extensionProperties);

        return ! $this->hasExtensionProperties($normalizedExtensionProperties)
            ? $openApi
            : $this->withExtensionProperties($openApi, $normalizedExtensionProperties);
    }

    /**
     * @param array<string, string|int|float|bool|array|null>|ArrayObject<string, string|int|float|bool|array|null>|null $extensionProperties
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    private function normalize(array|ArrayObject|null $extensionProperties): array
    {
        return match (true) {
            $extensionProperties instanceof ArrayObject => $extensionProperties->getArrayCopy(),
            is_array($extensionProperties) => $extensionProperties,
            default => [],
        };
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $extensionProperties
     */
    private function hasExtensionProperties(array $extensionProperties): bool
    {
        return $extensionProperties !== [];
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $extensionProperties
     */
    private function withExtensionProperties(OpenApi $openApi, array $extensionProperties): OpenApi
    {
        return array_reduce(
            array_keys($extensionProperties),
            static fn (OpenApi $api, string $key): OpenApi => $api->withExtensionProperty(
                $key,
                $extensionProperties[$key]
            ),
            $openApi
        );
    }
}
