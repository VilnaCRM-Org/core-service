<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

abstract class EndpointFactory implements EndpointFactoryInterface
{
    /**
     * @param ArrayObject<int|string, Response>|array<int|string, Response>|null $baseResponses
     * @param ArrayObject<int|string, Response>|array<int|string, Response> $overrideResponses
     *
     * @return array<int|string, Response>
     */
    public function mergeResponses(
        array|ArrayObject|null $baseResponses,
        array|ArrayObject $overrideResponses,
    ): array {
        return array_replace(
            $this->normalizeResponses($baseResponses),
            $this->normalizeResponses($overrideResponses),
        );
    }

    /**
     * @param array<int, Parameter> $parameters
     * @param array<int, Response> $responses
     */
    protected function applyOperation(
        OpenApi $openApi,
        string $endpointUri,
        string $operationName,
        array $parameters,
        array $responses,
        ?RequestBody $requestBody = null
    ): void {
        $pathItem = $openApi->getPaths()->getPath($endpointUri);
        $operation = $pathItem->{'get' . $operationName}();

        $mergedResponses = $this->mergeResponses(
            $operation->getResponses(),
            $responses
        );

        $updatedOperation = $parameters === []
            ? $operation
            : $operation->withParameters($parameters);

        $updatedOperation = $updatedOperation->withResponses($mergedResponses);
        $updatedOperation = $this->applyRequestBody(
            $updatedOperation,
            $requestBody
        );

        $openApi->getPaths()->addPath(
            $endpointUri,
            $pathItem->{'with' . $operationName}($updatedOperation)
        );
    }

    /**
     * @param ArrayObject<int|string, Response>|array<int|string, Response>|null $responses
     *
     * @return array<int|string, Response>
     */
    private function normalizeResponses(array|ArrayObject|null $responses): array
    {
        if ($responses === null) {
            return [];
        }

        return $responses instanceof ArrayObject
            ? $responses->getArrayCopy()
            : $responses;
    }

    private function applyRequestBody(
        Operation $operation,
        ?RequestBody $requestBody
    ): Operation {
        return $requestBody === null
            ? $operation
            : $operation->withRequestBody($requestBody);
    }
}
