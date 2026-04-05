<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Response;

final class OpenApiResponsesUpdater
{
    public function __construct(
        private OpenApiResponseSchemaFixer $responseSchemaFixer
    ) {
    }

    /**
     * @param array<int|string, Response|array>|null $responses
     *
     * @return array<int|string, Response|array>|null
     */
    public function update(?array $responses): ?array
    {
        return match ($responses) {
            null => null,
            default => $this->updatedResponsesOrNull($responses),
        };
    }

    /**
     * @param array<int|string, Response|array> $responses
     *
     * @return array<int|string, Response|array>
     */
    private function updatedResponses(array $responses): array
    {
        return array_map(
            $this->responseSchemaFixer->fix(...),
            $responses
        );
    }

    /**
     * @param array<int|string, Response|array> $responses
     *
     * @return array<int|string, Response|array>|null
     */
    private function updatedResponsesOrNull(array $responses): ?array
    {
        $updatedResponses = $this->updatedResponses($responses);

        return match ($this->responsesChanged($responses, $updatedResponses)) {
            true => $updatedResponses,
            default => null,
        };
    }

    /**
     * @param array<int|string, Response|array> $responses
     * @param array<int|string, Response|array> $updatedResponses
     */
    private function responsesChanged(array $responses, array $updatedResponses): bool
    {
        return $updatedResponses !== $responses;
    }
}
