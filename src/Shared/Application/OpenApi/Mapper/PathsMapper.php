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
    public function map(OpenApi $openApi, callable $callback): OpenApi
    {
        $newPaths = $this->createMappedPaths($openApi->getPaths(), $callback);
        $newOpenApi = $this->createOpenApiWithNewPaths($openApi, $newPaths);

        return $this->copyExtensionProperties($openApi, $newOpenApi);
    }

    private function createMappedPaths(Paths $paths, callable $callback): Paths
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

    private function createOpenApiWithNewPaths(OpenApi $openApi, Paths $newPaths): OpenApi
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

    private function copyExtensionProperties(OpenApi $source, OpenApi $target): OpenApi
    {
        foreach ($source->getExtensionProperties() as $key => $value) {
            $target = $target->withExtensionProperty($key, $value);
        }

        return $target;
    }
}
