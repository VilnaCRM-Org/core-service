<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;

final class TagDescriptionProcessor
{
    public function process(OpenApi $openApi): OpenApi
    {
        $tagDescriptions = $this->getTagDescriptions();
        $tags = $this->indexTags($openApi);

        foreach ($tagDescriptions as $tagName => $description) {
            $tag = $tags[$tagName] ?? new Tag($tagName);

            if ($this->isDescriptionEmpty($tag->getDescription())) {
                $tag = $tag->withDescription($description);
            }

            $tags[$tagName] = $tag;
        }

        return $openApi->withTags(array_values($tags));
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
        $indexedTags = [];

        foreach ($openApi->getTags() as $tag) {
            $indexedTags[$tag->getName()] = $tag;
        }

        return $indexedTags;
    }

    private static function isDescriptionEmpty(?string $description): bool
    {
        return ($description ?? '') === '';
    }
}
