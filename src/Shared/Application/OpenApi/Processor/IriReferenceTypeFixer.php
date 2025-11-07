<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class IriReferenceTypeFixer
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function __construct(
        private readonly ContentPropertyProcessor $contentProcessor
    ) {
    }

    public function fix(OpenApi $openApi): void
    {
        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $pathItem = $openApi->getPaths()->getPath($path);
            $openApi->getPaths()->addPath(
                $path,
                $this->fixPathItem($pathItem)
            );
        }
    }

    private function fixPathItem(PathItem $pathItem): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $pathItem->{'with' . $operation}(
                $this->fixOperation($pathItem->{'get' . $operation}())
            );
        }

        return $pathItem;
    }

    private function fixOperation(?Operation $operation): ?Operation
    {
        $content = $this->extractContent($operation);

        if (!$content instanceof ArrayObject) {
            return $operation;
        }

        if (!$this->contentProcessor->process($content)) {
            return $operation;
        }

        return $this->createUpdatedOperation($operation, $content);
    }

    private function extractContent(?Operation $operation): mixed
    {
        return $operation?->getRequestBody()?->getContent();
    }

    private function createUpdatedOperation(
        Operation $operation,
        ArrayObject $content
    ): Operation {
        return $operation->withRequestBody(
            $operation->getRequestBody()->withContent(
                new ArrayObject($content->getArrayCopy())
            )
        );
    }
}
