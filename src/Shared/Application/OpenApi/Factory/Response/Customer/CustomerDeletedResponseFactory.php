<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response\Customer;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\ResponseFactoryInterface;

final class CustomerDeletedResponseFactory implements ResponseFactoryInterface
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    #[Override]
    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Customer resource deleted',
            [],
            []
        );
    }
}
