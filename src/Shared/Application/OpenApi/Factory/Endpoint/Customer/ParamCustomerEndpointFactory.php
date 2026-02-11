<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\Customer;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\Customer\CreateCustomerRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\Customer\UpdateCustomerRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\Customer\CustomerDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\Customer\CustomerNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerUlidParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerEndpointFactory extends EndpointFactory
{
    private const ENDPOINT_URI = '/api/customers/{ulid}';

    private Parameter $ulidWithExamplePathParam;

    private RequestBody $updateCustomerRequest;

    private Response $validationResp;
    private Response $badRequestResp;
    private Response $custNotFoundResp;
    private Response $custDeletedResp;
    private Response $internalResp;
    private Response $unauthorizedResp;
    private Response $forbiddenResp;
    private RequestBody $replaceCustomerRequest;

    public function __construct(
        CustomerUlidParameterFactory $parameterFactory,
        UpdateCustomerRequestFactory $updateCustomerRequestFactory,
        ValidationErrorFactory $validationErrorResponseFactory,
        BadRequestResponseFactory $badRequestResponseFactory,
        CustomerNotFoundResponseFactory $customerNotFoundResponseFactory,
        CustomerDeletedResponseFactory $deletedResponseFactory,
        CreateCustomerRequestFactory $replaceCustomerRequestFactory,
        InternalErrorFactory $internalErrorFactory,
        UnauthorizedResponseFactory $unauthorizedResponseFactory,
        ForbiddenResponseFactory $forbiddenResponseFactory
    ) {
        $this->ulidWithExamplePathParam =
            $parameterFactory->getParameter();

        $this->updateCustomerRequest =
            $updateCustomerRequestFactory->getRequest();

        $this->validationResp =
            $validationErrorResponseFactory->getResponse();

        $this->badRequestResp =
            $badRequestResponseFactory->getResponse();

        $this->custNotFoundResp =
            $customerNotFoundResponseFactory->getResponse();

        $this->custDeletedResp =
            $deletedResponseFactory->getResponse();

        $this->replaceCustomerRequest =
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
        $this->setPutOperation($openApi);
        $this->setPatchOperation($openApi);
        $this->setGetOperation($openApi);
        $this->setDeleteOperation($openApi);
    }

    private function setPutOperation(OpenApi $openApi): void
    {
        $this->applyOperation(
            $openApi,
            self::ENDPOINT_URI,
            'Put',
            [$this->ulidWithExamplePathParam],
            $this->getUpdateResponses(),
            $this->replaceCustomerRequest
        );
    }

    private function setPatchOperation(OpenApi $openApi): void
    {
        $this->applyOperation(
            $openApi,
            self::ENDPOINT_URI,
            'Patch',
            [$this->ulidWithExamplePathParam],
            $this->getUpdateResponses(),
            $this->updateCustomerRequest
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
            HttpResponse::HTTP_NO_CONTENT => $this->custDeletedResp,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_NOT_FOUND => $this->custNotFoundResp,
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
            HttpResponse::HTTP_NOT_FOUND => $this->custNotFoundResp,
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
            HttpResponse::HTTP_NOT_FOUND => $this->custNotFoundResp,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }
}
