<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\EventListener;

final class SchemathesisSingleCustomerEmailExtractor
{
    /**
     * @param array<string, string|int|float|bool|array|null> $payload
     *
     * @return list<string>
     */
    public function extract(array $payload): array
    {
        $email = $payload['email'] ?? null;

        return is_string($email) ? [$email] : [];
    }
}
