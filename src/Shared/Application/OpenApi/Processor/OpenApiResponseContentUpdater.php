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
    private function updatedContentItems(array $contentItems): array
    {
        return array_map(
            $this->updatedDefinition(...),
            $contentItems
        );
    }

    private function updatedDefinition(array|object $definition): array|object
    {
        return $this->definitionUpdater->update($definition) ?? $definition;
    }
}
