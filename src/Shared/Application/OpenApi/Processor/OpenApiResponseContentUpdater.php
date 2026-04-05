<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\MediaType;
use ArrayObject;

final class OpenApiResponseContentUpdater
{
    public function __construct(
        private OpenApiContentDefinitionUpdater $definitionUpdater
    ) {
    }

    public function update(?ArrayObject $content): ?ArrayObject
    {
        if ($content === null) {
            return null;
        }

        $contentItems = $content->getArrayCopy();
        $updatedContentItems = $this->updatedContentItems($contentItems);

        return match ($updatedContentItems === $contentItems) {
            true => $content,
            default => new ArrayObject($updatedContentItems),
        };
    }

    /**
     * @param array<int|string, array|MediaType> $contentItems
     *
     * @return array<int|string, array|MediaType>
     */
    private function updatedContentItems(array $contentItems): array
    {
        return array_map(
            $this->updatedDefinition(...),
            $contentItems
        );
    }

    private function updatedDefinition(array|MediaType $definition): array|MediaType
    {
        return $this->definitionUpdater->update($definition) ?? $definition;
    }
}
