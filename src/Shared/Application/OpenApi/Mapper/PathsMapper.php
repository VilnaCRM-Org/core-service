<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Mapper;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class PathsMapper
{
    /**
     * @param callable(PathItem, string): PathItem $callback
     */
    public static function map(OpenApi $openApi, callable $callback): void
    {
        $paths = $openApi->getPaths();
        $pathKeys = array_keys($paths->getPaths());

        array_walk(
            $pathKeys,
            static function (string $path) use ($paths, $callback): void {
                $paths->addPath($path, $callback($paths->getPath($path), $path));
            }
        );
    }
}
