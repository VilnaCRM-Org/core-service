<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

use function is_array;

final class SpecExtensionPropertyApplier
{
    /**
     * @param array<string, string|int|float|bool|array|null>
     *        |ArrayObject<string, string|int|float|bool|array|null>
     *        |null $extensionProperties
     */
    public function apply(array|ArrayObject|null $extensionProperties, OpenApi $openApi): OpenApi
    {
        if (!is_array($extensionProperties) || $extensionProperties === []) {
            return $openApi;
        }

        foreach ($extensionProperties as $key => $value) {
            $openApi = $openApi->withExtensionProperty($key, $value);
        }

        return $openApi;
    }
}
