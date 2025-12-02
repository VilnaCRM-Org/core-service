<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;

final class TagDescriptionProcessor
{
    public function process(OpenApi $openApi): OpenApi
    {
        $tags = array_reduce(
            array_keys($this->getTagDescriptions()),
            fn (array $tags, string $name): array => [
                ...$tags,
                $name => $this->createOrUpdateTag($tags, $name),
            ],
            $this->indexTags($openApi)
        );

        return $openApi->withTags(array_values($tags));
    }

    /**
     * @param array<string, Tag> $tags
     */
    private function createOrUpdateTag(array $tags, string $tagName): Tag
    {
        $tag = $tags[$tagName] ?? new Tag($tagName);
        $description = $this->getTagDescriptions()[$tagName];

        return ($tag->getDescription() ?? '') === ''
            ? $tag->withDescription($description)
            : $tag;
    }

    /**
     * @return array<string, string>
     */
    private function getTagDescriptions(): array
    {
        return [
            'Customer' => 'Operations related to customer management',
            'CustomerStatus' => 'Operations related to customer status management',
            'CustomerType' => 'Operations related to customer type management',
            'HealthCheck' => 'Health check endpoints for monitoring',
        ];
    }

    /**
     * @return array<string, Tag>
     */
    private function indexTags(OpenApi $openApi): array
    {
        return array_reduce(
            $openApi->getTags(),
            static fn (array $indexed, Tag $tag): array => [...$indexed, $tag->getName() => $tag],
            []
        );
    }
}
