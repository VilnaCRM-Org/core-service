<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerStatus;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\CustomerStatusRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusCreatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusesReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerStatusEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_statuses';

    private Response $customerStatusCreatedResponse;

    private Response $customerStatusesReturnedResponse;

    private RequestBody $createCustomerStatusRequest;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;

    public function __construct(
        private CustomerStatusCreatedResponseFactory    $customerStatusCreatedResponseFactory,
        private CustomerStatusesReturnedResponseFactory $customerStatusReturnedResponseFactory,
        private CustomerStatusRequestFactory            $createCustomerStatusRequestFactory,
        private ValidationErrorFactory                  $validationErrorResponseFactory,
        private BadRequestResponseFactory               $badRequestResponseFactory,
    ) {
        $this->customerStatusCreatedResponse =
            $this->customerStatusCreatedResponseFactory->getResponse();

        $this->customerStatusesReturnedResponse =
            $this->customerStatusReturnedResponseFactory->getResponse();

        $this->createCustomerStatusRequest =
            $this->createCustomerStatusRequestFactory->getRequest();

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
                    ->withRequestBody($this->createCustomerStatusRequest)
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
            HttpResponse::HTTP_CREATED => $this->customerStatusCreatedResponse,
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
            HttpResponse::HTTP_OK => $this->customerStatusesReturnedResponse,
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
        ];
    }
}
