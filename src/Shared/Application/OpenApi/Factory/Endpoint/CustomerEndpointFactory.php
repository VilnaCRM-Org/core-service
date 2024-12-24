<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Response\CustomerCreatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomersReturnedResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customers';

    private Response $customerCreatedResponse;

    private Response $customersReturnedResponse;

    public function __construct(
        private CustomerCreatedResponseFactory  $customerCreatedResponseFactory,
        private CustomersReturnedResponseFactory $customerReturnedResponseFactory,
    ) {
        $this->customerCreatedResponse =
            $this->customerCreatedResponseFactory->getResponse();
        $this->customersReturnedResponse =
            $this->customerReturnedResponseFactory->getResponse();
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
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_OK => $this->customersReturnedResponse,
        ];
    }
}
