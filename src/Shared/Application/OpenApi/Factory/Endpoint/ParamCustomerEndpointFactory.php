<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\UpdateCustomerRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerUpdatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customers/{id}';

    private Parameter $uuidWithExamplePathParam;

    private Response $customerReturnedResponse;

    private RequestBody $updateCustomerRequest;

    private Response $customerUpdatedResponse;

    public function __construct(
        private UuidUriParameterFactory         $parameterFactory,
        private CustomerReturnedResponseFactory $customerReturnedResponseFactory,
        private UpdateCustomerRequestFactory    $updateCustomerRequestFactory,
        private CustomerUpdatedResponseFactory  $customerUpdatedResponseFactory,
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();

        $this->customerReturnedResponse =
            $this->customerReturnedResponseFactory->getResponse();

        $this->updateCustomerRequest =
            $this->updateCustomerRequestFactory->getRequest();

        $this->customerUpdatedResponse =
            $this->customerUpdatedResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $this->setPatchOperation($openApi);
        $this->setGetOperation($openApi);
        $this->setDeleteOperation($openApi);
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
                        ->withRequestBody($this->updateCustomerRequest)
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
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->customerReturnedResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->customerUpdatedResponse,
        ];
    }
}
