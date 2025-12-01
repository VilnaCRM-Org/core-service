<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class IriReferenceTypeProcessor
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];
    private IriReferenceContentTransformer $contentTransformer;

    public function __construct(?IriReferenceContentTransformer $contentTransformer = null)
    {
        $this->contentTransformer = $contentTransformer ?? new IriReferenceContentTransformer();
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        foreach (array_keys($paths->getPaths()) as $path) {
            $pathItem = $paths->getPath($path);

            foreach (self::OPERATIONS as $operation) {
                $pathItem = $this->updateOperation($pathItem, $operation);
            }

            $paths->addPath($path, $pathItem);
        }

        return $openApi;
    }

    private function updateOperation(PathItem $pathItem, string $operation): PathItem
    {
        $currentOperation = $pathItem->{'get' . $operation}();

        if (!$currentOperation instanceof Operation) {
            return $pathItem;
        }

        $requestBody = $currentOperation->getRequestBody();

        if (!$requestBody instanceof RequestBody) {
            return $pathItem;
        }

        $content = $requestBody->getContent();

        if (!$content instanceof ArrayObject) {
            return $pathItem;
        }

        $processedContent = $this->contentTransformer->transform($content);

        if ($processedContent === null) {
            return $pathItem;
        }

        $updatedOperation = $currentOperation->withRequestBody(
            $requestBody->withContent(new ArrayObject($processedContent))
        );

        return $pathItem->{'with' . $operation}($updatedOperation);
    }
}
