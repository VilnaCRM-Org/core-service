<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class OpenApiResponseContentUpdater
{
    public function __construct(
        private OpenApiContentDefinitionUpdater $definitionUpdater
    ) {
    }

    public function update(ArrayObject $content): ?ArrayObject
    {
        $contentItems = $content->getArrayCopy();
        $updatedContentItems = $this->updatedContentItems($contentItems);

        return match (true) {
            $updatedContentItems === $contentItems => null,
            default => new ArrayObject($updatedContentItems),
        };
    }

    /**
     * @param array<int|string, array|object> $contentItems
     *
     * @return array<int|string, array|object>
     */
    private function updatedContentItems($contentItems)
    {
        foreach ($contentItems as $mediaType => $definition) {
            $updatedDefinition = $this->definitionUpdater->update($definition);
            if ($updatedDefinition === null || $updatedDefinition === $definition) {
                continue;
            }

            $contentItems[$mediaType] = $updatedDefinition;
        }

        return $contentItems;
    }
}
