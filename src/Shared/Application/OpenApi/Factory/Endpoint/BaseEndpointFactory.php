<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Response;

abstract class BaseEndpointFactory implements EndpointFactoryInterface
{
    /**
     * @param array<int,Response> $baseResponses
     * @param array<int,Response> $overrideResponses
     *
     * @return array<int,Response>
     */
    public function mergeResponses(
        array $baseResponses,
        array $overrideResponses,
    ): array {
        return array_replace($baseResponses, $overrideResponses);
    }
}
