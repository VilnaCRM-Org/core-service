<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UnauthorizedResponseFactory implements ResponseFactoryInterface
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Unauthorized',
            [
                $this->getTypeParam(),
                $this->getTitleParam(),
                $this->getDetailParam(),
                $this->getStatusParam(),
            ],
            []
        );
    }

    public function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'string',
            'https://tools.ietf.org/html/rfc2616#section-10.4.2'
        );
    }

    public function getTitleParam(): Parameter
    {
        return new Parameter(
            'title',
            'string',
            'Unauthorized'
        );
    }

    public function getDetailParam(): Parameter
    {
        return new Parameter(
            'detail',
            'string',
            'Authentication credentials are missing or invalid.'
        );
    }

    public function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            HttpResponse::HTTP_UNAUTHORIZED
        );
    }
}
