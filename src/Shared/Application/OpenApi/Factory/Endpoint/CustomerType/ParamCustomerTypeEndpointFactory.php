<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint\CustomerType;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\CrCTyReq;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\UpCTyReq;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\CTyDelResp;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\TyNFResp;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriCustTy;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerTypeEndpointFactory extends EndpointFactory
{
    private const ENDPOINT_URI = '/api/customer_types/{ulid}';

    private Parameter $uuidWithExamplePathParam;

    private RequestBody $updateCustomerTypeRequest;
    private Response $validationResp;
    private Response $badRequestResp;
    private Response $custTyNFResp;
    private Response $custTyDeletedResp;
    private Response $internalResp;
    private Response $unauthorizedResp;
    private Response $forbiddenResp;
    private RequestBody $replaceCustomerTypeRequest;

    public function __construct(
        private UuidUriCustTy $parameterFactory,
        private UpCTyReq $updateCustomerTypeRequestFactory,
        private ValidationErrorFactory $validationErrorResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private TyNFResp $customerTyNFResp,
        private CTyDelResp $deletedResponseFactory,
        private CrCTyReq $replaceCustomerRequestFactory,
        private InternalErrorFactory $internalErrorFactory,
        private ForbiddenResponseFactory $forbiddenResponseFactory,
        private UnauthorizedResponseFactory $unauthorizedResponseFactory,
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();

        $this->updateCustomerTypeRequest =
            $this->updateCustomerTypeRequestFactory->getRequest();

        $this->validationResp =
            $this->validationErrorResponseFactory->getResponse();

        $this->badRequestResp =
            $this->badRequestResponseFactory->getResponse();

        $this->custTyNFResp =
            $this->customerTyNFResp->getResponse();

        $this->custTyDeletedResp =
            $this->deletedResponseFactory->getResponse();

        $this->replaceCustomerTypeRequest =
            $this->replaceCustomerRequestFactory->getRequest();

        $this->internalResp =
            $this->internalErrorFactory->getResponse();

        $this->forbiddenResp =
            $this->forbiddenResponseFactory->getResponse();

        $this->unauthorizedResp =
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
                        ->withParameters([$this->uuidWithExamplePathParam])
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
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem->withPut(
                $operationPut
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($mergedResponses)
                    ->withRequestBody($this->replaceCustomerTypeRequest)
            )
        );
    }

    private function setDeleteOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationDelete = $pathItem->getDelete();
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem->withDelete(
                $operationDelete
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($this->getDeleteResponses())
            )
        );
    }

    private function setGetOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationGet = $pathItem->getGet();
        $mergedResponses = $this->mergeResponses(
            $operationGet->getResponses(),
            $this->getGetResponses()
        );
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem->withGet(
                $operationGet
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withResponses($mergedResponses)
            )
        );
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
            HttpResponse::HTTP_NO_CONTENT => $this->custTyDeletedResp,
            HTTPResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HTTPResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_NOT_FOUND => $this->custTyNFResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getGetResponses(): array
    {
        return [
            HTTPResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HTTPResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_NOT_FOUND => $this->custTyNFResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }

    /**
     * @return array<int,Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResp,
            HTTPResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResp,
            HTTPResponse::HTTP_FORBIDDEN => $this->forbiddenResp,
            HttpResponse::HTTP_NOT_FOUND => $this->custTyNFResp,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validationResp,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResp,
        ];
    }
}
