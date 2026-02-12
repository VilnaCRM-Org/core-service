<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerType;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\TypeCreateRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\TypeUpdateRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\TypeDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\TypeNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerTypeUlidParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerTypeEndpointFactory extends EndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_types/{ulid}';

    private Parameter $ulidWithExamplePathParam;

    private RequestBody $updateCustomerTypeRequest;
    private Response $validationResp;
    private Response $badRequestResp;
    private Response $custTyNFResp;
    private Response $custTyDeletedResp;
    private Response $internalResp;
    private Response $unauthorizedResp;
    private Response $forbiddenResp;
    private RequestBody $replaceCustomerTypeRequest;

    public function __construct(
        CustomerTypeUlidParameterFactory $parameterFactory,
        TypeUpdateRequestFactory $updateCustomerTypeRequestFactory,
        ValidationErrorFactory $validationErrorResponseFactory,
        BadRequestResponseFactory $badRequestResponseFactory,
        TypeNotFoundResponseFactory $customerTyNFResp,
        TypeDeletedResponseFactory $deletedResponseFactory,
        TypeCreateRequestFactory $replaceCustomerRequestFactory,
        InternalErrorFactory $internalErrorFactory,
        ForbiddenResponseFactory $forbiddenResponseFactory,
        UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->ulidWithExamplePathParam =
            $parameterFactory->getParameter();

        $this->updateCustomerTypeRequest =
            $updateCustomerTypeRequestFactory->getRequest();

        $this->validationResp =
            $validationErrorResponseFactory->getResponse();

        $this->badRequestResp =
            $badRequestResponseFactory->getResponse();

        $this->custTyNFResp =
            $customerTyNFResp->getResponse();

        $this->custTyDeletedResp =
            $deletedResponseFactory->getResponse();

        $this->replaceCustomerTypeRequest =
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
            $this->updateCustomerTypeRequest
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
            $this->replaceCustomerTypeRequest
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
            HttpResponse::HTTP_NO_CONTENT => $this->custTyDeletedResp,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_NOT_FOUND => $this->custTyNFResp,
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
            HttpResponse::HTTP_NOT_FOUND => $this->custTyNFResp,
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
            HttpResponse::HTTP_NOT_FOUND => $this->custTyNFResp,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }
}
