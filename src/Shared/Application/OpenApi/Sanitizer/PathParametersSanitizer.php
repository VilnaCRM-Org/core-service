<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Sanitizer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleanerInterface;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;

final class PathParametersSanitizer
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    private readonly PathParameterCleanerInterface $parameterCleaner;

    public function __construct(
        PathParameterCleanerInterface $parameterCleaner = new PathParameterCleaner()
    ) {
        $this->parameterCleaner = $parameterCleaner;
    }

    public function sanitize(OpenApi $openApi): OpenApi
    {
        PathsMapper::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this->sanitizePathItem($pathItem)
        );

        return $openApi;
    }

    private function sanitizePathItem(PathItem $pathItem): PathItem
    {
        return array_reduce(
            self::OPERATIONS,
            fn (PathItem $item, string $operation): PathItem => $item->{'with' . $operation}(
                $this->sanitizeOperation($item->{'get' . $operation}())
            ),
            $pathItem
        );
    }

    private function sanitizeOperation(?Operation $operation): ?Operation
    {
        return match (true) {
            $operation === null => null,
            !\is_array($operation->getParameters()) => $operation,
            default => $operation->withParameters(
                array_map(
                    $this->sanitizeParameter(...),
                    $operation->getParameters()
                )
            ),
        };
    }

    private function sanitizeParameter(\ApiPlatform\OpenApi\Model\Parameter|array $parameter): mixed
    {
        return $this->parameterCleaner->clean($parameter);
    }
}
