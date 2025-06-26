<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\Endpoint\Status;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Core\Customer\Application\OpenApi\BaseEndpointFactory;
use App\Core\Customer\Application\OpenApi\Request\Status\StatusCreateFactory;
use App\Core\Customer\Application\OpenApi\Request\Status\StatusUpdateFactory;
use App\Core\Customer\Application\OpenApi\Response\Status\DeletedFactory;
use App\Core\Customer\Application\OpenApi\Response\Status\NotFoundFactory;
use App\Core\Customer\Application\OpenApi\UriParameter\CustomerStatusFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @psalm-suppress-file UnusedProperty
 */
final class StatusParamFactory extends BaseEndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_statuses/{ulid}';

    private Parameter $ulidWithExamplePathParam;
    private RequestBody $updateCustomerStatusRequest;
    private Response $validResponse;
    private Response $badRequestResponse;
    private Response $customerStatusNotFoundResponse;
    private Response $customerStatusDeletedResponse;
    private Response $internalResponse;
    private Response $unauthorizedResponse;
    private Response $forbiddenResponse;
    private RequestBody $replaceCustomerStatusRequest;

    public function __construct(
        /** @psalm-suppress UnusedProperty */
        private CustomerStatusFactory $parameterFactory,
        /** @psalm-suppress UnusedProperty */
        private StatusUpdateFactory $updateCustomerStatusRequestFactory,
        /** @psalm-suppress UnusedProperty */
        private ValidationErrorFactory $validationErrorResponseFactory,
        /** @psalm-suppress UnusedProperty */
        private BadRequestResponseFactory $badRequestResponseFactory,
        /** @psalm-suppress UnusedProperty */
        private NotFoundFactory $customerStatusNotFoundFactory,
        /** @psalm-suppress UnusedProperty */
        private DeletedFactory $deletedResponseFactory,
        /** @psalm-suppress UnusedProperty */
        private StatusCreateFactory $replaceCustomerFactory,
        /** @psalm-suppress UnusedProperty */
        private InternalErrorFactory $internalErrorFactory,
        /** @psalm-suppress UnusedProperty */
        private ForbiddenResponseFactory $forbiddenResponseFactory,
        /** @psalm-suppress UnusedProperty */
        private UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->ulidWithExamplePathParam = $this->parameterFactory->getParameter();
        $this->updateCustomerStatusRequest = $this->updateCustomerStatusRequestFactory->getRequest();
        $this->validResponse = $this->validationErrorResponseFactory->getResponse();
        $this->badRequestResponse = $this->badRequestResponseFactory->getResponse();
        $this->customerStatusNotFoundResponse = $this->customerStatusNotFoundFactory->getResponse();
        $this->customerStatusDeletedResponse = $this->deletedResponseFactory->getResponse();
        $this->replaceCustomerStatusRequest = $this->replaceCustomerFactory->getRequest();
        $this->internalResponse = $this->internalErrorFactory->getResponse();
        $this->forbiddenResponse = $this->forbiddenResponseFactory->getResponse();
        $this->unauthorizedResponse = $this->unauthorizedResponseFactory->getResponse();
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
        $mergedResponses = $this->mergeResponses(
            $operationPut->getResponses(),
            $this->getUpdateResponses()
        );

        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPut(
                $operationPut
                    ->withParameters([$this->ulidWithExamplePathParam])
                    ->withResponses($mergedResponses)
                    ->withRequestBody($this->replaceCustomerStatusRequest)
            ));
    }

    private function setPatchOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPatch = $pathItem->getPatch();
        $mergedResponses = $this->mergeResponses(
            $operationPatch->getResponses(),
            $this->getUpdateResponses()
        );
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem
                ->withPatch(
                    $operationPatch
                        ->withParameters([$this->ulidWithExamplePathParam])
                        ->withRequestBody($this->updateCustomerStatusRequest)
                        ->withResponses($mergedResponses)
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
                    ->withParameters([$this->ulidWithExamplePathParam])
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
                $operationGet->withParameters([$this->ulidWithExamplePathParam])
                    ->withResponses($mergedResponses)
            ));
    }

    private function getPathItem(OpenApi $openApi): PathItem|null
    {
        $paths = $openApi->getPaths();
        return $paths->getPath(self::ENDPOINT_URI);
    }

    /**
     * @return array<int, Response>
     */
    private function getDeleteResponses(): array
    {
        return [
            HttpResponse::HTTP_NO_CONTENT => $this->customerStatusDeletedResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerStatusNotFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    /**
     * @return array<int, Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerStatusNotFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    /**
     * @return array<int, Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerStatusNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }
}
