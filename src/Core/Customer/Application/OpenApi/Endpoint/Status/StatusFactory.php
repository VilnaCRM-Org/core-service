<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\Endpoint\Status;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Core\Customer\Application\OpenApi\BaseEndpointFactory;
use App\Core\Customer\Application\OpenApi\Request\Status\StatusCreateFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class StatusFactory extends BaseEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_statuses';

    private RequestBody $createCustomerStatusRequest;
    private Response $validResponse;
    private Response $badRequestResponse;
    private Response $internalResponse;
    private Response $unauthorizedResponse;
    private Response $forbiddenResponse;

    public function __construct(
        StatusCreateFactory $createCustomerStatusRequestFactory,
        ValidationErrorFactory $validationErrorResponseFactory,
        BadRequestResponseFactory $badRequestResponseFactory,
        InternalErrorFactory $internalErrorFactory,
        ForbiddenResponseFactory $forbiddenResponseFactory,
        UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->createCustomerStatusRequest =
            $createCustomerStatusRequestFactory->getRequest();

        $this->validResponse =
            $validationErrorResponseFactory->getResponse();

        $this->badRequestResponse =
            $badRequestResponseFactory->getResponse();

        $this->internalResponse =
            $internalErrorFactory->getResponse();

        $this->forbiddenResponse =
            $forbiddenResponseFactory->getResponse();

        $this->unauthorizedResponse =
            $unauthorizedResponseFactory->getResponse();
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
                    ->withRequestBody($this->createCustomerStatusRequest)
            )
            ->withGet($operationGet->withResponses(
                $mergedGet
            )));
    }

    /**
     * @return array<Response>
     *
     * @psalm-return array{400: Response, 401: Response, 403: Response, 422: Response, 500: Response}
     */
    private function getPostResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    /**
     * @return array<Response>
     *
     * @psalm-return array{400: Response, 401: Response, 403: Response, 500: Response}
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }
}
