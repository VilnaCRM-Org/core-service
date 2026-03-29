<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerStatus;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\StatusCreateRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\StatusUpdateRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\StatusDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\StatusNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerStatusUlidParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerStatusEndpointFactory extends EndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_statuses/{ulid}';

    private Parameter $ulidWithExamplePathParam;

    private RequestBody $updateCustomerStatusRequest;
    private Response $validationResp;
    private Response $badRequestResp;
    private Response $notFoundResp;
    private Response $custStDeletedResp;
    private Response $internalResp;
    private Response $unauthorizedResp;
    private Response $forbiddenResp;
    private RequestBody $replaceCustomerStatusRequest;

    public function __construct(
        CustomerStatusUlidParameterFactory $parameterFactory,
        StatusUpdateRequestFactory $updateCustomerStatusRequestFactory,
        ValidationErrorFactory $validationErrorResponseFactory,
        BadRequestResponseFactory $badRequestResponseFactory,
        StatusNotFoundResponseFactory $customerStatusNotFoundResponseFactory,
        StatusDeletedResponseFactory $deletedResponseFactory,
        StatusCreateRequestFactory $replaceCustomerRequestFactory,
        InternalErrorFactory $internalErrorFactory,
        ForbiddenResponseFactory $forbiddenResponseFactory,
        UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->ulidWithExamplePathParam =
            $parameterFactory->getParameter();

        $this->updateCustomerStatusRequest =
            $updateCustomerStatusRequestFactory->getRequest();

        $this->validationResp =
            $validationErrorResponseFactory->getResponse();

        $this->badRequestResp =
            $badRequestResponseFactory->getResponse();

        $this->notFoundResp =
            $customerStatusNotFoundResponseFactory->getResponse();

        $this->custStDeletedResp =
            $deletedResponseFactory->getResponse();

        $this->replaceCustomerStatusRequest =
            $replaceCustomerRequestFactory->getRequest();

        $this->internalResp =
            $internalErrorFactory->getResponse();

        $this->forbiddenResp =
            $forbiddenResponseFactory->getResponse();

        $this->unauthorizedResp =
            $unauthorizedResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $this->setPatchOperation($openApi);
        $this->setPutOperation($openApi);
        $this->setGetOperation($openApi);
        $this->setDeleteOperation($openApi);
    }

    private function setPatchOperation(OpenApi $openApi): void
    {
        $this->applyOperation(
            $openApi,
            self::ENDPOINT_URI,
            'Patch',
            [$this->ulidWithExamplePathParam],
            $this->getUpdateResponses(),
            $this->updateCustomerStatusRequest
        );
    }

    private function setPutOperation(OpenApi $openApi): void
    {
        $this->applyOperation(
            $openApi,
            self::ENDPOINT_URI,
            'Put',
            [$this->ulidWithExamplePathParam],
            $this->getUpdateResponses(),
            $this->replaceCustomerStatusRequest
        );
    }

    private function setDeleteOperation(OpenApi $openApi): void
    {
        $this->applyOperation(
            $openApi,
            self::ENDPOINT_URI,
            'Delete',
            [$this->ulidWithExamplePathParam],
            $this->getDeleteResponses()
        );
    }

    private function setGetOperation(OpenApi $openApi): void
    {
        $this->applyOperation(
            $openApi,
            self::ENDPOINT_URI,
            'Get',
            [$this->ulidWithExamplePathParam],
            $this->getGetResponses()
        );
    }

    /**
     * @return array<int,Response>
     */
    private function getDeleteResponses(): array
    {
        return [
            HttpResponse::HTTP_NO_CONTENT => $this->custStDeletedResp,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResp,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResp,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }
}
