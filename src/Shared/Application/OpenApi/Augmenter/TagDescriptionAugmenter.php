<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Augmenter;

use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;

final class TagDescriptionAugmenter
{
    public function augment(OpenApi $openApi): OpenApi
    {
        $tagDescriptions = $this->getTagDescriptions();

        $tags = array_map(
            static fn (Tag $tag) => self::augmentTag($tag, $tagDescriptions),
            $openApi->getTags()
        );

        return $openApi->withTags($tags);
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
     * @param array<string, string> $descriptions
     */
    private static function augmentTag(Tag $tag, array $descriptions): Tag
    {
        $tagName = $tag->getName();
        $description = $descriptions[$tagName] ?? null;

        return $description !== null && self::isDescriptionEmpty($tag->getDescription())
            ? $tag->withDescription($description)
            : $tag;
    }

    private static function isDescriptionEmpty(?string $description): bool
    {
        return $description === null || $description === '';
    }
}
