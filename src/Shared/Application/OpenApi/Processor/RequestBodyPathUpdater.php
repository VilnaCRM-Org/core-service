<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;

final class RequestBodyPathUpdater
{
    public function __construct(
        private readonly RequestBodySchemaRefUpdater $requestBodyUpdater
    ) {
    }

    /**
     * @param array<string, string> $schemaRefsByOperation
     */
    public function update(PathItem $pathItem, array $schemaRefsByOperation): PathItem
    {
        foreach ($schemaRefsByOperation as $operationName => $schemaRef) {
            $getter = 'get' . $operationName;
            $wither = 'with' . $operationName;
            $operation = $pathItem->{$getter}();

            if (! $operation instanceof Operation) {
                continue;
            }

            $updatedOperation = $this->requestBodyUpdater->update(
                $operation,
                $schemaRef
            );

            if ($updatedOperation === $operation) {
                continue;
            }

            $pathItem = $pathItem->{$wither}($updatedOperation);
        }

        return $pathItem;
    }
}
