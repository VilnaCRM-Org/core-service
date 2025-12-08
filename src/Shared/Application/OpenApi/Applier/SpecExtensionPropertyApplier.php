<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Applier;

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
        return !is_array($extensionProperties) || $extensionProperties === []
            ? $openApi
            : array_reduce(
                array_keys($extensionProperties),
                static fn (OpenApi $api, string $key): OpenApi => $api->withExtensionProperty(
                    $key,
                    $extensionProperties[$key]
                ),
                $openApi
            );
    }
}
