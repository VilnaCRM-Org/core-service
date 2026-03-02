<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Mapper\PathItemOperationMapper;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;
use App\Shared\Application\OpenApi\Resolver\IriReferenceOperationContextResolverInterface;
use App\Shared\Application\OpenApi\Transformer\IriReferenceContentTransformerInterface;
use App\Shared\Application\OpenApi\ValueObject\IriReferenceOperationContext;
use ArrayObject;

final class IriReferenceTypeProcessor
{
    public function __construct(
        private readonly IriReferenceContentTransformerInterface $contentTransformer,
        private readonly IriReferenceOperationContextResolverInterface $contextResolver
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        return PathsMapper::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this->processPathItem($pathItem)
        );
    }

    private function processPathItem(PathItem $pathItem): PathItem
    {
        return PathItemOperationMapper::map(
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
