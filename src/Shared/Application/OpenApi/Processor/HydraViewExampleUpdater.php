<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

final class HydraViewExampleUpdater
{
    public function __construct(
        private HydraAllOfUpdater $allOfUpdater
    ) {
    }

    /**
     * @param array<string, mixed> $normalized
     *
     * @return array<string, mixed>|null
     */
    public function update(array $normalized): ?array
    {
        $allOf = $normalized['allOf'] ?? null;
        if (! is_array($allOf)) {
            return null;
        }

        $updatedAllOf = $this->allOfUpdater->update($allOf);
        if ($updatedAllOf === null) {
            return null;
        }

        $normalized['allOf'] = $updatedAllOf;

        return $normalized;
    }
}
