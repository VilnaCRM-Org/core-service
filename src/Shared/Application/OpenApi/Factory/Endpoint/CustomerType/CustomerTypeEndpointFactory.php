<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerType;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\CustTypeCreateReqFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerTypeEndpointFactory extends AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_types';

    private RequestBody $createCustomerTypeRequest;
    private Response $validationResponse;
    private Response $badRequestResponse;
    private Response $internalErrorResponse;
    private Response $unauthorizedResponse;
    private Response $forbiddenResponse;

    public function __construct(
        private CustTypeCreateReqFactory $createCustomerTypeRequestFactory,
        private ValidationErrorFactory $validationErrorResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private InternalErrorFactory $internalErrorFactory,
        private ForbiddenResponseFactory $forbiddenResponseFactory,
        private UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->createCustomerTypeRequest =
            $this->createCustomerTypeRequestFactory->getRequest();

        $this->validationResponse =
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
                    ->withRequestBody($this->createCustomerTypeRequest)
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
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationResponse,
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
