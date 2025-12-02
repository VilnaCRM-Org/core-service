<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;

final class IriReferenceOperationContextResolver implements
    IriReferenceOperationContextResolverInterface
{
    public function resolve(PathItem $pathItem, string $operation): ?IriReferenceOperationContext
    {
        $operationInstance = $pathItem->{'get' . $operation}();
        if (!$operationInstance instanceof Operation) {
            return null;
        }

        $requestBody = $operationInstance->getRequestBody();
        if (!$requestBody instanceof RequestBody) {
            return null;
        }

        $content = $requestBody->getContent();
        if (!$content instanceof ArrayObject) {
            return null;
        }

        return new IriReferenceOperationContext($operationInstance, $requestBody, $content);
    }
}
