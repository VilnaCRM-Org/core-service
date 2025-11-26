<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ForbiddenResponseFactory implements ResponseFactoryInterface
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Forbidden',
            [
                $this->getTypeParam(),
                $this->getTitleParam(),
                $this->getDetailParam(),
                $this->getStatusParam(),
            ],
            []
        );
    }

    private function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'string',
            'https://tools.ietf.org/html/rfc2616#section-10.4.4'
        );
    }

    private function getTitleParam(): Parameter
    {
        return new Parameter(
            'title',
            'string',
            'Forbidden'
        );
    }

    private function getDetailParam(): Parameter
    {
        return new Parameter(
            'detail',
            'string',
            'The server understood the request, but refuses to authorize it.'
        );
    }

    private function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            HttpResponse::HTTP_FORBIDDEN
        );
    }
}
