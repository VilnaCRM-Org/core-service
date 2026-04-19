<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 * @phpstan-type ContentDefinition array<string, SchemaValue>
 */
final class RequestBodyContentSchemaRefUpdater
{
    public function __construct(
        private readonly RequestBodySchemaRefDefinitionUpdater $definitionUpdater
    ) {
    }

    /**
     * @param ArrayObject<string, ContentDefinition> $content
     *
     * @return ArrayObject<string, ContentDefinition>|null
     */
    public function update(ArrayObject $content, string $schemaRef): ?ArrayObject
    {
        $updatedContent = $content->getArrayCopy();
        $changed = false;

        foreach ($updatedContent as $contentType => $definition) {
            if (! \is_array($definition)) {
                continue;
            }

            $updatedDefinition = $this->definitionUpdater->update(
                $definition,
                $schemaRef
            );

            if ($updatedDefinition === null) {
                continue;
            }

            $updatedContent[$contentType] = $updatedDefinition;
            $changed = true;
        }

        return $changed ? new ArrayObject($updatedContent) : null;
    }
}
