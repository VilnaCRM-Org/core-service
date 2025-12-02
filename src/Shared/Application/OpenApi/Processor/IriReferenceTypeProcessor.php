<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class IriReferenceTypeProcessor
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

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
        $paths = $openApi->getPaths();

        foreach (array_keys($paths->getPaths()) as $path) {
            $current = $paths->getPath($path);
            $paths->addPath($path, $this->processPathItem($current));
        }

        return $openApi;
    }

    private function processPathItem(PathItem $pathItem): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $this->transformOperation($pathItem, $operation);
        }

        return $pathItem;
    }

    private function transformOperation(PathItem $pathItem, string $operation): PathItem
    {
        $context = $this->contextResolver->resolve($pathItem, $operation);

        if ($context === null) {
            return $pathItem;
        }

        $processedContent = $this->contentTransformer->transform($context->content);

        if ($processedContent === null) {
            return $pathItem;
        }

        return $this->withTransformedOperation($pathItem, $operation, $context, $processedContent);
    }

    /**
     * @param array<string, scalar|array<string, scalar|null>> $processedContent
     */
    private function withTransformedOperation(
        PathItem $pathItem,
        string $operation,
        IriReferenceOperationContext $context,
        array $processedContent
    ): PathItem {
        $updatedOperation = $context->operation->withRequestBody(
            $context->requestBody->withContent(new ArrayObject($processedContent))
        );

        return $pathItem->{'with' . $operation}($updatedOperation);
    }
}
