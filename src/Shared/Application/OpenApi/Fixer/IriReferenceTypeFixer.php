<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Fixer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Support\PathItemOperations;
use App\Shared\Application\OpenApi\Support\PathsManipulator;
use ArrayObject;

final class IriReferenceTypeFixer
{
    public function __construct(
        private readonly ContentPropertyFixer $contentPropertyFixer
    ) {
    }

    public function fix(OpenApi $openApi): void
    {
        PathsManipulator::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this->applyOperations($pathItem)
        );
    }

    private function applyOperations(PathItem $pathItem): PathItem
    {
        return PathItemOperations::map(
            $pathItem,
            fn (Operation $operation): Operation => $this
                ->fixOperation($operation)
        );
    }

    private function fixOperation(Operation $operation): Operation
    {
        $content = $operation->getRequestBody()?->getContent();

        $canFixContent = $content instanceof ArrayObject
            && $this->contentPropertyFixer->fix($content);

        return $canFixContent
            ? $operation->withRequestBody(
                $operation->getRequestBody()->withContent(
                    new ArrayObject($content->getArrayCopy())
                )
            )
            : $operation;
    }
}
