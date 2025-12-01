<?php

declare(strict_types=1);

namespace App\Shared\Application\Request;

final class PatchUlidExtractor
{
    /**
     * @param array<string, string> $uriVariables
     * @param callable(): \Throwable $exceptionFactory
     */
    public function extract(
        array $uriVariables,
        ?string $bodyIdentifier,
        callable $exceptionFactory
    ): string {
        if (isset($uriVariables['ulid'])) {
            return $uriVariables['ulid'];
        }

        if ($bodyIdentifier !== null) {
            return basename($bodyIdentifier);
        }

        throw $exceptionFactory();
    }
}
