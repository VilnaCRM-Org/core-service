<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\Customer;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\Customer\CustomerCreateRequestFactory;
use App\Shared\Application\OpenApi\Factory\Request\Customer\UpdateCustomerRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\Customer\CustomerDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\Customer\CustomerNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriCustomerFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerEndpointFactory extends AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customers/{ulid}';

    private Parameter $uuidWithExamplePathParam;

    private RequestBody $updateCustomerRequest;

    private Response $validationErrorResponse;
    private Response $badRequestResponse;
    private Response $customerNotFoundResponse;
    private Response $customerDeletedResponse;
    private Response $internalErrorResponse;
    private Response $unauthorizedResponse;
    private Response $forbiddenResponse;
    private RequestBody $replaceCustomerRequest;

    public function __construct(
        private UuidUriCustomerFactory          $parameterFactory,
        private UpdateCustomerRequestFactory    $updateCustomerRequestFactory,
        private ValidationErrorFactory          $validationErrorResponseFactory,
        private BadRequestResponseFactory       $badRequestResponseFactory,
        private CustomerNotFoundResponseFactory $customerNotFoundResponseFactory,
        private CustomerDeletedResponseFactory  $deletedResponseFactory,
        private CustomerCreateRequestFactory    $replaceCustomerRequestFactory,
        private InternalErrorFactory            $internalErrorFactory,
        private ForbiddenResponseFactory        $forbiddenResponseFactory,
        private UnauthorizedResponseFactory     $unauthorizedResponseFactory,
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();

        $this->updateCustomerRequest =
            $this->updateCustomerRequestFactory->getRequest();

        $this->validationErrorResponse =
            $this->validationErrorResponseFactory->getResponse();

        $this->badRequestResponse =
            $this->badRequestResponseFactory->getResponse();

        $this->customerNotFoundResponse =
            $this->customerNotFoundResponseFactory->getResponse();

        $this->customerDeletedResponse =
            $this->deletedResponseFactory->getResponse();

        $this->replaceCustomerRequest =
            $this->replaceCustomerRequestFactory->getRequest();

        $this->internalErrorResponse =
            $this->internalErrorFactory->getResponse();

        $this->forbiddenResponse =
            $this->forbiddenResponseFactory->getResponse();

        $this->unauthorizedResponse =
            $this->unauthorizedResponseFactory->getResponse();
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
        $mergedResponses = $this->mergeResponses($operationPut->getResponses(), $this->getUpdateResponses());

        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withPut(
                $operationPut
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($mergedResponses)
                    ->withRequestBody($this->replaceCustomerRequest)
            ));
    }

    private function setPatchOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPatch = $pathItem->getPatch();
        $mergedResponses = $this->mergeResponses($operationPatch->getResponses(), $this->getUpdateResponses());
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem
                ->withPatch(
                    $operationPatch
                        ->withParameters([$this->uuidWithExamplePathParam])
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
                    ->withParameters([$this->uuidWithExamplePathParam])
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
                $operationGet->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($mergedResponses)
            ));
    }

    private function getPathItem(OpenApi $openApi): PathItem
    {
        $paths = $openApi->getPaths();
        return $paths->getPath(self::ENDPOINT_URI);
    }

    /**
     * @return array<int,Response>
     */
    private function getDeleteResponses(): array
    {
        return [
            HttpResponse::HTTP_NO_CONTENT => $this->customerDeletedResponse,
            HTTPResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HTTPResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalErrorResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HTTPResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HTTPResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalErrorResponse,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HTTPResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HTTPResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationErrorResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalErrorResponse,
        ];
    }
}
