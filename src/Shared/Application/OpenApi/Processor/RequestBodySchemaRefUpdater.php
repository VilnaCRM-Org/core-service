<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;

final class RequestBodySchemaRefUpdater
{
    public function __construct(
        private readonly RequestBodyContentSchemaRefUpdater $contentUpdater
    ) {
    }

    public function update(Operation $operation): Operation
    {
        $requestBody = $operation->getRequestBody();
        $content = $requestBody?->getContent();

        if (! $requestBody instanceof RequestBody || ! $content instanceof ArrayObject) {
            return $operation;
        }

        $updatedContent = $this->contentUpdater->update($content);

        return $updatedContent === null
            ? $operation
            : $operation->withRequestBody(
                $requestBody->withContent($updatedContent)
            );
    }
}
