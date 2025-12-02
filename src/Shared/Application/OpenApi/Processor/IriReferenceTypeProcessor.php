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

        // Pattern: Replace foreach with array_walk (functional composition)
        array_walk(
            array_keys($paths->getPaths()),
            fn (string $path) => $paths->addPath($path, $this->processPathItem($paths->getPath($path)))
        );

        return $openApi;
    }

    private function processPathItem(PathItem $pathItem): PathItem
    {
        return array_reduce(
            self::OPERATIONS,
            fn (PathItem $item, string $op): PathItem => $this->transformOperation($item, $op),
            $pathItem
        );
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

        // Pattern: Inline trivial method (reduces method count)
        $updatedOperation = $context->operation->withRequestBody(
            $context->requestBody->withContent(new ArrayObject($processedContent))
        );

        return $pathItem->{'with' . $operation}($updatedOperation);
    }
}
