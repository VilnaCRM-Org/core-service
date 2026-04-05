<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Response;
use ArrayObject;

final class OpenApiResponseSchemaFixer
{
    public function __construct(
        private OpenApiResponseContentUpdater $contentUpdater
    ) {
    }

    public function fix(Response|array $response): Response|array
    {
        return match (true) {
            ! $response instanceof Response => $response,
            default => $this->fixedResponse($response),
        };
    }

    private function updatedContent(Response $response): ?ArrayObject
    {
        return $this->contentUpdater->update($response->getContent());
    }

    private function fixedResponse(Response $response): Response
    {
        $updatedContent = $this->updatedContent($response);

        return match ($updatedContent === $response->getContent()) {
            true => $response,
            default => $response->withContent($updatedContent),
        };
    }
}
