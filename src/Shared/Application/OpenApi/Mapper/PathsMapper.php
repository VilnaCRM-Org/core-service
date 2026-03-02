<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Mapper;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;

final class PathsMapper
{
    /**
     * @param callable(PathItem, string): PathItem $callback
     */
    public static function map(OpenApi $openApi, callable $callback): OpenApi
    {
        $newPaths = self::createMappedPaths($openApi->getPaths(), $callback);
        $newOpenApi = self::createOpenApiWithNewPaths($openApi, $newPaths);

        return self::copyExtensionProperties($openApi, $newOpenApi);
    }

    private static function createMappedPaths(Paths $paths, callable $callback): Paths
    {
        $newPaths = new Paths();
        $pathKeys = array_keys($paths->getPaths());

        array_walk(
            $pathKeys,
            static fn (string $path) => $newPaths->addPath(
                $path,
                $callback($paths->getPath($path), $path)
            )
        );

        return $newPaths;
    }

    private static function createOpenApiWithNewPaths(OpenApi $openApi, Paths $newPaths): OpenApi
    {
        return new OpenApi(
            $openApi->getInfo(),
            $openApi->getServers(),
            $newPaths,
            $openApi->getComponents(),
            $openApi->getSecurity(),
            $openApi->getTags(),
            $openApi->getExternalDocs(),
            $openApi->getJsonSchemaDialect(),
            $openApi->getWebhooks()
        );
    }

    private static function copyExtensionProperties(OpenApi $source, OpenApi $target): OpenApi
    {
        foreach ($source->getExtensionProperties() as $key => $value) {
            $target = $target->withExtensionProperty($key, $value);
        }

        return $target;
    }
}
