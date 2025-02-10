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
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriCustomerStatusFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ParamCustomerStatusEndpointFactory extends AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_statuses/{ulid}';

    private Parameter $uuidWithExamplePathParam;

    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $customerStatusNotFoundResponse;
    private Response $customerStatusDeletedResponse;
    private RequestBody $replaceCustomerStatusRequest;

    public function __construct(
        private UuidUriCustomerStatusFactory       $parameterFactory,
        private ValidationErrorFactory                $validationErrorResponseFactory,
        private BadRequestResponseFactory             $badRequestResponseFactory,
        private CustomerStatusNotFoundResponseFactory $customerStatusNotFoundResponseFactory,
        private CustomerStatusDeletedResponseFactory  $deletedResponseFactory,
        private CustomerStatusRequestFactory          $replaceCustomerRequestFactory,
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();

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
        $this->setGetOperation($openApi);
        $this->setDeleteOperation($openApi);
    }

    private function setPutOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPut = $pathItem->getPut();
        $mergedResponses = $this->mergeResponses($operationPut->getResponses(), $this->getUpdateResponses());
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPut(
                $operationPut
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($mergedResponses)
                    ->withRequestBody($this->replaceCustomerStatusRequest)
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
        $mergedResponses = $this->mergeResponses(
            $operationGet->getResponses(),
            $this->getGetResponses()
        );
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withGet(
                $operationGet->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($mergedResponses)
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
            HttpResponse::HTTP_NOT_FOUND => $this->customerStatusNotFoundResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerStatusNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
        ];
    }
}
