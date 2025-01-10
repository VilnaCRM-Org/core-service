<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\CustomerRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerCreatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomersReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customers';

    private Response $customerCreatedResponse;

    private Response $customersReturnedResponse;

    private RequestBody $createCustomerRequest;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;

    public function __construct(
        private CustomerCreatedResponseFactory   $customerCreatedResponseFactory,
        private CustomersReturnedResponseFactory $customerReturnedResponseFactory,
        private CustomerRequestFactory           $createCustomerRequestFactory,
        private ValidationErrorFactory          $validationErrorResponseFactory,
        private BadRequestResponseFactory       $badRequestResponseFactory,
    ) {
        $this->customerCreatedResponse =
            $this->customerCreatedResponseFactory->getResponse();

        $this->customersReturnedResponse =
            $this->customerReturnedResponseFactory->getResponse();

        $this->createCustomerRequest =
            $this->createCustomerRequestFactory->getRequest();

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
                    ->withRequestBody($this->createCustomerRequest)
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
            HttpResponse::HTTP_CREATED => $this->customerCreatedResponse,
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
            HttpResponse::HTTP_OK => $this->customersReturnedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
        ];
    }
}
