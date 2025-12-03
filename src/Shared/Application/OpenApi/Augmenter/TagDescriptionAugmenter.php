<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Augmenter;

use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\TagDescriptionDictionary;

final class TagDescriptionAugmenter
{
    public function augment(OpenApi $openApi): OpenApi
    {
        $tagDescriptions = TagDescriptionDictionary::descriptions();

        $tags = array_map(
            static fn (Tag $tag) => self::augmentTag($tag, $tagDescriptions),
            $openApi->getTags()
        );

        return $openApi->withTags($tags);
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
        return ($description ?? '') === '';
    }
}
