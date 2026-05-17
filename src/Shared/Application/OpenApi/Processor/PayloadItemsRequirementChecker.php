<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class PayloadItemsRequirementChecker
{
    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null>|null $payload
     */
    public function shouldAddItems(?array $payload): bool
    {
        return $this->isArrayPayload($payload) && ($payload['items'] ?? null) === null;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null>|null $payload
     */
    private function isArrayPayload(?array $payload): bool
    {
        return in_array('array', $this->types($payload), true);
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null>|null $payload
     *
     * @return array<int|string, array|bool|float|int|string|ArrayObject|null>
     */
    private function types(?array $payload): array
    {
        $type = \is_array($payload) ? ($payload['type'] ?? []) : [];

        return \is_string($type) ? [$type] : (new SchemaNormalizer())->normalize($type);
    }
}
