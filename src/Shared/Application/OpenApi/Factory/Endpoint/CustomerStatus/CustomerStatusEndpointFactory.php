<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerStatus;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\CustomerCreateStatusRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerStatusEndpointFactory extends EndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_statuses';

    private RequestBody $createCustomerStatusRequest;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $internalErrorResponse;
    private Response $unauthorizedResponse;
    private Response $forbiddenResponse;

    public function __construct(
        private CustomerCreateStatusRequestFactory $createCustomerStatusRequestFactory,
        private ValidationErrorFactory $validationErrorResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private InternalErrorFactory $internalErrorFactory,
        private ForbiddenResponseFactory $forbiddenResponseFactory,
        private UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->createCustomerStatusRequest =
            $this->createCustomerStatusRequestFactory->getRequest();

        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();

        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();

        $this->internalErrorResponse =
            $this->internalErrorFactory->getResponse();

        $this->forbiddenResponse =
            $this->forbiddenResponseFactory->getResponse();

        $this->unauthorizedResponse =
            $this->unauthorizedResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::ENDPOINT_URI);
        $operationPost = $pathItem->getPost();
        $operationGet = $pathItem->getGet();
        $mergedGet = $this->mergeResponses(
            $operationGet->getResponses(),
            $this->getGetResponses()
        );
        $mergedPost = $this->mergeResponses(
            $operationPost->getResponses(),
            $this->getPostResponses()
        );
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPost(
                $operationPost
                    ->withResponses($mergedPost)
                    ->withRequestBody($this->createCustomerStatusRequest)
            )
            ->withGet($operationGet->withResponses(
                $mergedGet
            )));
    }

    /**
     * @return array<int,Response>
     */
    private function getPostResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HTTPResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HTTPResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalErrorResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HTTPResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HTTPResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalErrorResponse,
        ];
    }
}
