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

        foreach ($normalizedExtensionProperties as $key => $value) {
            $openApi = $openApi->withExtensionProperty($key, $value);
        }

        return $openApi;
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
}
