<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Fixer;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\ContentPropertyProcessor;
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

            $pathItem = array_reduce(
                self::OPERATIONS,
                function (PathItem $item, string $operation): PathItem {
                    return $this->fixOperation($item, $operation);
                },
                $pathItem
            );

            $openApi->getPaths()->addPath($path, $pathItem);
        }
    }

    private function fixOperation(PathItem $pathItem, string $operation): PathItem
    {
        $currentOperation = $pathItem->{'get' . $operation}();
        $content = $currentOperation?->getRequestBody()?->getContent();

        if (
            !$content instanceof ArrayObject
            || !$this->contentProcessor->process($content)
        ) {
            return $pathItem;
        }

        $updatedOperation = $currentOperation->withRequestBody(
            $currentOperation->getRequestBody()->withContent(
                new ArrayObject($content->getArrayCopy())
            )
        );

        return $pathItem->{'with' . $operation}($updatedOperation);
    }
}
