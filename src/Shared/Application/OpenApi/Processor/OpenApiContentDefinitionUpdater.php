<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\MediaType;

final class OpenApiContentDefinitionUpdater
{
    public function __construct(
        private OpenApiMediaTypeSchemaFixer $mediaTypeSchemaFixer,
        private OpenApiArrayContentSchemaUpdater $arrayContentSchemaUpdater
    ) {
    }

    public function update(array|MediaType $definition): array|MediaType|null
    {
        return $definition instanceof MediaType
            ? $this->mediaTypeSchemaFixer->fix($definition)
            : $this->arrayContentSchemaUpdater->update($definition);
    }
}
