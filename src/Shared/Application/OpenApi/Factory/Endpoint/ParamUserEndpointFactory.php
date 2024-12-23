<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamUserEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/customers/{id}';

    private Parameter $uuidWithExamplePathParam;

    public function __construct(
        private UuidUriParameterFactory $parameterFactory,
    ) {
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $this->setPatchOperation($openApi);
        $this->setGetOperation($openApi);
        $this->setDeleteOperation($openApi);
    }

    private function setPatchOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationPatch = $pathItem->getPatch();
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem
                ->withPatch(
                    $operationPatch
                        ->withParameters([$this->uuidWithExamplePathParam])

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
            ));
    }

    private function setGetOperation(OpenApi $openApi): void
    {
        $pathItem = $this->getPathItem($openApi);
        $operationGet = $pathItem->getGet();
        $openApi->getPaths()->addPath(self::ENDPOINT_URI, $pathItem
            ->withGet(
                $operationGet->withParameters([$this->uuidWithExamplePathParam])
            ));
    }

    private function getPathItem(OpenApi $openApi): PathItem
    {
        return $openApi->getPaths()->getPath(self::ENDPOINT_URI);
    }
}
