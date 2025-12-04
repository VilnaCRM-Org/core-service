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
        $existingTagNames = $this->extractUniqueExistingTagNames($openApi->getTags());
        $operationTagNames = $this->collectTagsFromOperations($openApi);
        $mergedTagNames = $this->mergeUniqueTagNames($existingTagNames, $operationTagNames);

        return $openApi->withTags($this->createTagsFromNames($mergedTagNames));
    }

    /**
     * @param array<string> $existing
     * @param array<string> $operation
     *
     * @return array<string>
     */
    private function mergeUniqueTagNames(array $existing, array $operation): array
    {
        $merged = array_merge($existing, array_diff($operation, $existing));
        sort($merged);

        return $merged;
    }

    /**
     * @param array<string> $tagNames
     *
     * @return array<Tag>
     */
    private function createTagsFromNames(array $tagNames): array
    {
        return array_map(
            static fn (string $tagName): Tag => new Tag($tagName),
            $tagNames
        );
    }

    /**
     * @param array<int|string, Tag> $tags
     *
     * @return array<string>
     */
    private function extractUniqueExistingTagNames(array $tags): array
    {
        return $this->uniqueTagNames($this->extractExistingTagNames($tags));
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
        $pathItems = array_filter(
            $openApi->getPaths()->getPaths(),
            static fn (?PathItem $pathItem): bool => $pathItem instanceof PathItem
        );

        $tags = array_reduce(
            $pathItems,
            fn (array $collected, PathItem $pathItem): array => array_merge(
                $collected,
                $this->collectTagsFromPathItem($pathItem)
            ),
            []
        );

        return array_unique($tags);
    }

    /**
     * @return array<string>
     */
    private function collectTagsFromPathItem(PathItem $pathItem): array
    {
        return array_reduce(
            $this->getOperations($pathItem),
            static fn (array $collected, Operation $operation): array => array_merge(
                $collected,
                $operation->getTags() ?? []
            ),
            []
        );
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
