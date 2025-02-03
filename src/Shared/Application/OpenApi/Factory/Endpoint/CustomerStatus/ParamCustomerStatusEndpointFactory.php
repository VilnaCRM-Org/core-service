<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerStatus;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\CustomerStatusRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\UpdateCustomerStatusRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusUpdatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriCustomerStatusFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ParamCustomerStatusEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_statuses/{id}';

    private Parameter $uuidWithExamplePathParam;

    private Response $customerStatusReturnedResponse;

    private RequestBody $updateCustomerStatusRequest;

    private Response $customerStatusUpdatedResponse;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $customerStatusNotFoundResponse;
    private Response $customerStatusDeletedResponse;
    private RequestBody $replaceCustomerStatusRequest;

    public function __construct(
        private UuidUriCustomerStatusFactory       $parameterFactory,
        private CustomerStatusReturnedResponseFactory $customerStatusReturnedResponseFactory,
        private UpdateCustomerStatusRequestFactory    $updateCustomerStatusRequestFactory,
        private CustomerStatusUpdatedResponseFactory  $customerStatusUpdatedResponseFactory,
        private ValidationErrorFactory                $validationErrorResponseFactory,
        private BadRequestResponseFactory             $badRequestResponseFactory,
        private CustomerStatusNotFoundResponseFactory $customerStatusNotFoundResponseFactory,
        private CustomerStatusDeletedResponseFactory  $deletedResponseFactory,
        private CustomerStatusRequestFactory          $replaceCustomerRequestFactory,
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();

        $this->customerStatusReturnedResponse =
            $this->customerStatusReturnedResponseFactory->getResponse();

        $this->updateCustomerStatusRequest =
            $this->updateCustomerStatusRequestFactory->getRequest();

        $this->customerStatusUpdatedResponse =
            $this->customerStatusUpdatedResponseFactory->getResponse();

        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();

        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();

        $this->customerStatusNotFoundResponse =
            $this->customerStatusNotFoundResponseFactory->getResponse();

        $this->customerStatusDeletedResponse =
            $this->deletedResponseFactory->getResponse();

        $this->replaceCustomerStatusRequest =
            $this->replaceCustomerRequestFactory->getRequest();
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
        $pathItem = $this->getPathItem($openApi);
        $operationPut = $pathItem->getPut();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPut(
                $operationPut
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($this->getUpdateResponses())
                    ->withRequestBody($this->replaceCustomerStatusRequest)
            ));
    }

    private function setPatchOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPatch = $pathItem->getPatch();
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem
                ->withPatch(
                    $operationPatch
                        ->withParameters([$this->uuidWithExamplePathParam])
                        ->withRequestBody($this->updateCustomerStatusRequest)
                        ->withResponses($this->getUpdateResponses())
                )
        );
    }

    private function setDeleteOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationDelete = $pathItem->getDelete();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withDelete(
                $operationDelete
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($this->getDeleteResponses())
            ));
    }

    private function setGetOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationGet = $pathItem->getGet();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withGet(
                $operationGet->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($this->getGetResponses())
            ));

    }

    private function getPathItem(OpenApi $openApi): PathItem
    {
        return $openApi->getPaths()->getPath(self::ENDPOINT_URI);
    }

    /**
     * @return array<int,Response>
     */
    private function getDeleteResponses(): array
    {
        return [
            HttpResponse::HTTP_NO_CONTENT => $this->customerStatusDeletedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerStatusNotFoundResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->customerStatusReturnedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerStatusNotFoundResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->customerStatusUpdatedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerStatusNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
        ];
    }
}
