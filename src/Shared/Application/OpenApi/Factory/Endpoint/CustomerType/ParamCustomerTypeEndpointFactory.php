<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerType;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\CustomerTypeRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\UpdateCustomerTypeRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\CustomerTypeDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\CustomerTypeNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\CustomerTypeReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\CustomerTypeUpdatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriCustomerTypeFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ParamCustomerTypeEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_types/{id}';

    private Parameter $uuidWithExamplePathParam;

    private Response $customerTypeReturnedResponse;

    private Response $customerTypeUpdatedResponse;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $customerTypeNotFoundResponse;
    private Response $customerTypeDeletedResponse;
    private RequestBody $replaceCustomerTypeRequest;

    public function __construct(
        private UuidUriCustomerTypeFactory     $parameterFactory,
        private CustomerTypeReturnedResponseFactory $customerTypeReturnedResponseFactory,
        private CustomerTypeUpdatedResponseFactory  $customerTypeUpdatedResponseFactory,
        private ValidationErrorFactory              $validationErrorResponseFactory,
        private BadRequestResponseFactory           $badRequestResponseFactory,
        private CustomerTypeNotFoundResponseFactory $customerTypeNotFoundResponseFactory,
        private CustomerTypeDeletedResponseFactory  $deletedResponseFactory,
        private CustomerTypeRequestFactory          $replaceCustomerRequestFactory,
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();

        $this->customerTypeReturnedResponse =
            $this->customerTypeReturnedResponseFactory->getResponse();

        $this->customerTypeUpdatedResponse =
            $this->customerTypeUpdatedResponseFactory->getResponse();

        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();

        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();

        $this->customerTypeNotFoundResponse =
            $this->customerTypeNotFoundResponseFactory->getResponse();

        $this->customerTypeDeletedResponse =
            $this->deletedResponseFactory->getResponse();

        $this->replaceCustomerTypeRequest =
            $this->replaceCustomerRequestFactory->getRequest();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $this->setPutOperation($openApi);
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
                    ->withRequestBody($this->replaceCustomerTypeRequest)
            ));
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
            HttpResponse::HTTP_NO_CONTENT => $this->customerTypeDeletedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerTypeNotFoundResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->customerTypeReturnedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerTypeNotFoundResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->customerTypeUpdatedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerTypeNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
        ];
    }
}
