<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

abstract class AbstractEndpointFactory implements AbstractEndpointFactoryInterface
{
    public function mergeResponses(
        array $baseResponses,
        array $overrideResponses,
    ): array {
        return array_replace($baseResponses, $overrideResponses);
    }
}
