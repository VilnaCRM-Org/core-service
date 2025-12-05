<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Applier;

use ApiPlatform\OpenApi\OpenApi;

/**
 * Applies extension properties to an OpenAPI document.
 */
final class OpenApiExtensionsApplier
{
    /**
     * @param array<string, scalar|array|\ArrayObject|null> $extensions
     */
    public function apply(OpenApi $openApi, array $extensions): OpenApi
    {
        return array_reduce(
            array_keys($extensions),
            static function (OpenApi $carry, string $name) use ($extensions): OpenApi {
                return $carry->withExtensionProperty($name, $extensions[$name]);
            },
            $openApi
        );
    }
}
