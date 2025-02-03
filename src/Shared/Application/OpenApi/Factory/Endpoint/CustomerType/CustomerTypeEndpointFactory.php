<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerType;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\CustomerTypeRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\CustomerTypeCreatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\CustomerTypesReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerTypeEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_types';

    private Response $customerTypeCreatedResponse;

    private Response $customerTypesReturnedResponse;

    private RequestBody $createCustomerTypeRequest;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;

    public function __construct(
        private CustomerTypeCreatedResponseFactory   $customerTypeCreatedResponseFactory,
        private CustomerTypesReturnedResponseFactory $customerTypeReturnedResponseFactory,
        private CustomerTypeRequestFactory           $createCustomerTypeRequestFactory,
        private ValidationErrorFactory               $validationErrorResponseFactory,
        private BadRequestResponseFactory            $badRequestResponseFactory,
    ) {
        $this->customerTypeCreatedResponse =
            $this->customerTypeCreatedResponseFactory->getResponse();

        $this->customerTypesReturnedResponse =
            $this->customerTypeReturnedResponseFactory->getResponse();

        $this->createCustomerTypeRequest =
            $this->createCustomerTypeRequestFactory->getRequest();

        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();

        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::ENDPOINT_URI);
        $operationPost = $pathItem->getPost();
        $operationGet = $pathItem->getGet();

        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPost(
                $operationPost
                    ->withResponses($this->getPostResponses())
                    ->withRequestBody($this->createCustomerTypeRequest)
            )
            ->withGet($operationGet->withResponses(
                $this->getGetResponses()
            )));
    }

    /**
     * @return array<int,Response>
     */
    private function getPostResponses(): array
    {
        return [
            HttpResponse::HTTP_CREATED => $this->customerTypeCreatedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->customerTypesReturnedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
        ];
    }
}
