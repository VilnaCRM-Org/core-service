<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Fixer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Mapper\PathItemOperationMapper;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;
use ArrayObject;

final class IriReferenceTypeFixer
{
    public function __construct(
        private readonly ContentPropertyFixer $contentPropertyFixer
    ) {
    }

    public function fix(OpenApi $openApi): void
    {
        PathsMapper::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this->applyOperations($pathItem)
        );
    }

    private function applyOperations(PathItem $pathItem): PathItem
    {
        return PathItemOperationMapper::map(
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
