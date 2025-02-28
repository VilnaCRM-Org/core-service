<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerType;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\Type\TypeCreateFactory;
use App\Shared\Application\OpenApi\Factory\Request\Type\TypeUpdateFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\Type\DeletedFactory;
use App\Shared\Application\OpenApi\Factory\Response\Type\NotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerTypeFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerTypeEndpointFactory extends EndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_types/{ulid}';

    private Parameter $ulidWithExamplePathParam;

    private RequestBody $updateCustomerTypeRequest;
    private Response $validResponse;
    private Response $badRequestResponse;
    private Response $customerTypeNotFoundResponse;
    private Response $customerTypeDeletedResponse;
    private Response $internalResponse;
    private Response $unauthorizedResponse;
    private Response $forbiddenResponse;
    private RequestBody $replaceCustomerTypeRequest;

    public function __construct(
        private CustomerTypeFactory $parameterFactory,
        private TypeUpdateFactory $updateCustomerTypeFactory,
        private ValidationErrorFactory $validationErrorResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private NotFoundFactory $customerTypeNotFoundFactory,
        private DeletedFactory $deletedResponseFactory,
        private TypeCreateFactory $replaceCustomerRequestFactory,
        private InternalErrorFactory $internalErrorFactory,
        private ForbiddenResponseFactory $forbiddenResponseFactory,
        private UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->ulidWithExamplePathParam =
            $this->parameterFactory->getParameter();

        $this->updateCustomerTypeRequest =
            $this->updateCustomerTypeFactory->getRequest();

        $this->validResponse =
            $this->validationErrorResponseFactory->getResponse();

        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();

        $this->customerTypeNotFoundResponse =
            $this->customerTypeNotFoundFactory->getResponse();

        $this->customerTypeDeletedResponse =
            $this->deletedResponseFactory->getResponse();

        $this->replaceCustomerTypeRequest =
            $this->replaceCustomerRequestFactory->getRequest();

        $this->internalResponse =
            $this->internalErrorFactory->getResponse();

        $this->forbiddenResponse =
            $this->forbiddenResponseFactory->getResponse();

        $this->unauthorizedResponse =
            $this->unauthorizedResponseFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $this->setPatchOperation($openApi);
        $this->setPutOperation($openApi);
        $this->setGetOperation($openApi);
        $this->setDeleteOperation($openApi);
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
                        ->withRequestBody($this->updateCustomerTypeRequest)
                        ->withResponses($mergedResponses)
                )
        );
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
                    ->withRequestBody($this->replaceCustomerTypeRequest)
            ));
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
            HttpResponse::HTTP_NO_CONTENT => $this->customerTypeDeletedResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerTypeNotFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerTypeNotFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerTypeNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }
}
