<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\Customer;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\Customer\CreateCustomerRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerEndpointFactory extends EndpointFactory
{
    private const ENDPOINT_URI = '/api/customers';

    private RequestBody $createCustomerRequest;
    private Response $validationResp;
    private Response $badRequestResp;
    private Response $internalResp;
    private Response $unauthorizedResp;
    private Response $forbiddenResp;

    public function __construct(
        CreateCustomerRequestFactory $createCustomerRequestFactory,
        ValidationErrorFactory $validationErrorResponseFactory,
        BadRequestResponseFactory $badRequestResponseFactory,
        InternalErrorFactory $internalErrorFactory,
        ForbiddenResponseFactory $forbiddenResponseFactory,
        UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->createCustomerRequest =
            $createCustomerRequestFactory->getRequest();

        $this->validationResp =
            $validationErrorResponseFactory->getResponse();

        $this->badRequestResp =
            $badRequestResponseFactory->getResponse();

        $this->internalResp =
            $internalErrorFactory->getResponse();

        $this->forbiddenResp =
            $forbiddenResponseFactory->getResponse();

        $this->unauthorizedResp =
            $unauthorizedResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $this->applyOperation(
            $openApi,
            self::ENDPOINT_URI,
            'Post',
            [],
            $this->getPostResponses(),
            $this->createCustomerRequest
        );

        $this->applyOperation(
            $openApi,
            self::ENDPOINT_URI,
            'Get',
            [],
            $this->getGetResponses()
        );
    }

    /**
     * @return array<int,Response>
     */
    private function getPostResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResp,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResp,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }
}
