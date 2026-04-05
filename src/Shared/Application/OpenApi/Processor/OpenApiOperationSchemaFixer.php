<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;

final class OpenApiOperationSchemaFixer
{
    public function __construct(
        private OpenApiResponsesUpdater $responsesUpdater
    ) {
    }

    public function fix(Operation $operation): Operation
    {
        $updatedResponses = $this->responsesUpdater->update($operation->getResponses());

        return match ($updatedResponses) {
            null => $operation,
            default => $operation->withResponses($updatedResponses),
        };
    }
}
