<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Mapper;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;

final class PathItemOperationMapper
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    /**
     * @param callable(Operation, string): Operation $callback
     */
    public static function map(PathItem $pathItem, callable $callback): PathItem
    {
        $operations = array_filter(
            array_map(
                static fn (string $operationName): ?array => self::buildOperationPayload(
                    $pathItem,
                    $operationName
                ),
                self::OPERATIONS
            )
        );

        return array_reduce(
            $operations,
            static fn (PathItem $item, array $operationData): PathItem => $item
                ->{'with' . $operationData['name']}(
                    $callback($operationData['operation'], $operationData['name'])
                ),
            $pathItem
        );
    }

    /**
     * @return array{name: string, operation: Operation}|null
     */
    private static function buildOperationPayload(
        PathItem $pathItem,
        string $operationName
    ): ?array {
        $operation = $pathItem->{'get' . $operationName}();

        return $operation instanceof Operation
            ? [
                'name' => $operationName,
                'operation' => $operation,
            ]
            : null;
    }
}
