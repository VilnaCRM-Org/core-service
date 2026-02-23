<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Resolver;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use App\Shared\Application\OpenApi\ValueObject\IriReferenceOperationContext;
use ArrayObject;

final class IriReferenceOperationContextResolver implements
    IriReferenceOperationContextResolverInterface
{
    public function resolve(PathItem $pathItem, string $operation): ?IriReferenceOperationContext
    {
        $operationInstance = $pathItem->{'get' . $operation}();

        return $operationInstance instanceof Operation
            ? $this->createContext($operationInstance)
            : null;
    }

    private function createContext(Operation $operation): ?IriReferenceOperationContext
    {
        $requestBody = $operation->getRequestBody();
        $content = $requestBody?->getContent();

        return $content instanceof ArrayObject
            ? new IriReferenceOperationContext($operation, $requestBody, $content)
            : null;
    }
}
