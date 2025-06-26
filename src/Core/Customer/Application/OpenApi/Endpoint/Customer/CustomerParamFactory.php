<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\Endpoint\Customer;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Core\Customer\Application\OpenApi\BaseEndpointFactory;
use App\Core\Customer\Application\OpenApi\Request\Customer\CreateFactory;
use App\Core\Customer\Application\OpenApi\Request\Customer\UpdateFactory;
use App\Core\Customer\Application\OpenApi\Response\Customer\DeletedFactory;
use App\Core\Customer\Application\OpenApi\Response\Customer\NotFoundFactory;
use App\Core\Customer\Application\OpenApi\UriParameter\CustomerFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @psalm-suppress-file UnusedProperty
 */
final class CustomerParamFactory extends BaseEndpointFactory
{
    private const ENDPOINT_URI = '/api/customers/{ulid}';

    private Parameter $ulidWithExamplePathParam;
    private RequestBody $updateCustomerRequest;
    private Response $validResponse;
    private Response $badRequestResponse;
    private Response $customerNotFoundResponse;
    private Response $customerDeletedResponse;
    private Response $internalResponse;
    private Response $unauthorizedResponse;
    private Response $forbiddenResponse;
    private RequestBody $replaceCustomerRequest;

    public function __construct(
        /** @psalm-suppress UnusedProperty */
        private CustomerFactory $parameterFactory,
        /** @psalm-suppress UnusedProperty */
        private UpdateFactory $updateCustomerRequestFactory,
        /** @psalm-suppress UnusedProperty */
        private ValidationErrorFactory $validationErrorResponseFactory,
        /** @psalm-suppress UnusedProperty */
        private BadRequestResponseFactory $badRequestResponseFactory,
        /** @psalm-suppress UnusedProperty */
        private NotFoundFactory $customerNotFoundFactory,
        /** @psalm-suppress UnusedProperty */
        private DeletedFactory $deletedResponseFactory,
        /** @psalm-suppress UnusedProperty */
        private CreateFactory $replaceCustomerRequestFactory,
        /** @psalm-suppress UnusedProperty */
        private InternalErrorFactory $internalErrorFactory,
        /** @psalm-suppress UnusedProperty */
        private ForbiddenResponseFactory $forbiddenResponseFactory,
        /** @psalm-suppress UnusedProperty */
        private UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->ulidWithExamplePathParam = $this->parameterFactory->getParameter();
        $this->updateCustomerRequest = $this->updateCustomerRequestFactory->getRequest();
        $this->validResponse = $this->validationErrorResponseFactory->getResponse();
        $this->badRequestResponse = $this->badRequestResponseFactory->getResponse();
        $this->customerNotFoundResponse = $this->customerNotFoundFactory->getResponse();
        $this->customerDeletedResponse = $this->deletedResponseFactory->getResponse();
        $this->replaceCustomerRequest = $this->replaceCustomerRequestFactory->getRequest();
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
                    ->withRequestBody($this->replaceCustomerRequest)
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
                        ->withRequestBody($this->updateCustomerRequest)
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
            HttpResponse::HTTP_NO_CONTENT => $this->customerDeletedResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
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
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
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
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }
}
