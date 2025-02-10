<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerType;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\CustomerTypeRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerTypeEndpointFactory extends AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_types';

    private RequestBody $createCustomerTypeRequest;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;

    public function __construct(
        private CustomerTypeRequestFactory           $createCustomerTypeRequestFactory,
        private ValidationErrorFactory               $validationErrorResponseFactory,
        private BadRequestResponseFactory            $badRequestResponseFactory,
    ) {

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
        $mergedGet = $this->mergeResponses(
            $operationGet->getResponses(),
            $this->getGetResponses()
        );
        $mergedPost = $this->mergeResponses(
            $operationPost->getResponses(),
            $this->getPostResponses()
        );
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPost(
                $operationPost
                    ->withResponses($mergedPost)
                    ->withRequestBody($this->createCustomerTypeRequest)
            )
            ->withGet($operationGet->withResponses(
                $mergedGet
            )));
    }

    /**
     * @return array<int,Response>
     */
    private function getPostResponses(): array
    {
        return [
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
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
        ];
    }
}
