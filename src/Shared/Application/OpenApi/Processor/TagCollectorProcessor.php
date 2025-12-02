<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;

final class TagCollectorProcessor
{
    public function process(OpenApi $openApi): OpenApi
    {
        $existingTags = $this->uniqueTagNames(
            $this->extractExistingTagNames($openApi->getTags())
        );
        $operationTags = $this->collectTagsFromOperations($openApi);

        $allTagNames = array_merge(
            $existingTags,
            array_diff($operationTags, $existingTags)
        );
        sort($allTagNames);

        $tags = array_map(
            static fn (string $tagName): Tag => new Tag($tagName),
            $allTagNames
        );

        return $openApi->withTags($tags);
    }

    /**
     * @param array<int|string, Tag> $tags
     *
     * @return array<string>
     */
    private function extractExistingTagNames(array $tags): array
    {
        return array_map(
            static fn (Tag $tag): string => $tag->getName(),
            $tags
        );
    }

    /**
     * @param array<string> $tags
     *
     * @return array<string>
     */
    private function uniqueTagNames(array $tags): array
    {
        return array_keys(array_flip($tags));
    }

    /**
     * @return array<string>
     */
    private function collectTagsFromOperations(OpenApi $openApi): array
    {
        $tags = [];

        foreach ($openApi->getPaths()->getPaths() as $pathItem) {
            assert($pathItem instanceof PathItem);
            $tags = array_merge($tags, $this->collectTagsFromPathItem($pathItem));
        }

        return array_unique($tags);
    }

    /**
     * @return array<string>
     */
    private function collectTagsFromPathItem(PathItem $pathItem): array
    {
        $tags = [];

        foreach ($this->getOperations($pathItem) as $operation) {
            $tags = array_merge($tags, $operation->getTags() ?? []);
        }

        return $tags;
    }

    /**
     * @return array<Operation>
     */
    private function getOperations(PathItem $pathItem): array
    {
        return array_filter([
            $pathItem->getGet(),
            $pathItem->getPost(),
            $pathItem->getPut(),
            $pathItem->getPatch(),
            $pathItem->getDelete(),
            $pathItem->getHead(),
            $pathItem->getOptions(),
            $pathItem->getTrace(),
        ]);
    }
}
