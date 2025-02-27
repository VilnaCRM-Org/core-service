<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response\CustomerStatus;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\ResponseFactoryInterface;

final class CustomerStatusDeletedFactory implements ResponseFactoryInterface
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'CustomerStatus resource deleted',
            [],
            []
        );
    }
}
