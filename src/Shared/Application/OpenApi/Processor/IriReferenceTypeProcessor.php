<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Support\PathItemOperations;
use App\Shared\Application\OpenApi\Support\PathsManipulator;
use ArrayObject;

final class IriReferenceTypeProcessor
{
    private IriReferenceContentTransformerInterface $contentTransformer;
    private IriReferenceOperationContextResolverInterface $contextResolver;

    public function __construct(
        ?IriReferenceContentTransformerInterface $contentTransformer = null,
        ?IriReferenceOperationContextResolverInterface $contextResolver = null
    ) {
        $this->contentTransformer = $contentTransformer ?? new IriReferenceContentTransformer();
        $this->contextResolver = $contextResolver ?? new IriReferenceOperationContextResolver();
    }

    public function process(OpenApi $openApi): OpenApi
    {
        PathsManipulator::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this->processPathItem($pathItem)
        );

        return $openApi;
    }

    private function processPathItem(PathItem $pathItem): PathItem
    {
        return PathItemOperations::map(
            $pathItem,
            fn (Operation $operation, string $operationName): Operation => $this
                ->transformOperation($pathItem, $operation, $operationName)
        );
    }

    private function transformOperation(
        PathItem $pathItem,
        Operation $operation,
        string $operationName
    ): Operation {
        $context = $this->contextResolver->resolve($pathItem, $operationName);

        return $context === null
            ? $operation
            : $this->updateOperationFromContext($context, $operation);
    }

    private function updateOperationFromContext(
        IriReferenceOperationContext $context,
        Operation $defaultOperation
    ): Operation {
        $processedContent = $this->contentTransformer->transform($context->content);

        return $processedContent === null
            ? $defaultOperation
            : $context->operation->withRequestBody(
                $context->requestBody->withContent(new ArrayObject($processedContent))
            );
    }
}
