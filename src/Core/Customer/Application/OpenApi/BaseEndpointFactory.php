<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;

abstract class BaseEndpointFactory implements EndpointFactoryInterface
{
    /**
     * @param array<int,Response> $baseResponses
     * @param array<int,Response> $overrideResponses
     *
     * @return array<Response>
     *
     * @psalm-return list<Response>
     */
    protected function mergeResponses(
        array $baseResponses,
        array $overrideResponses,
    ): array {
        return array_replace($baseResponses, $overrideResponses);
    }
}
