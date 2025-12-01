<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Domain\Entity\Customer;

use function trim;

final class CustomerUpdateScalarResolver
{
    private const STRING_FIELDS = [
        'initials' => 'getInitials',
        'email' => 'getEmail',
        'phone' => 'getPhone',
        'leadSource' => 'getLeadSource',
    ];

    /**
     * @param array{
     *     initials?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     leadSource?: string|null
     * } $input
     *
     * @return array{
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     leadSource: string
     * }
     */
    public function resolveStrings(Customer $customer, array $input): array
    {
        $resolved = [];

        foreach (self::STRING_FIELDS as $field => $getter) {
            $current = $customer->{$getter}();
            $resolved[$field] = $this->stringValue($input[$field] ?? null, $current);
        }

        return $resolved;
    }

    /**
     * @param array{
     *     confirmed?: bool|int|string|null
     * } $input
     */
    public function resolveConfirmed(Customer $customer, array $input): bool
    {
        $candidate = $input['confirmed'] ?? null;

        if ($candidate === null) {
            return $customer->isConfirmed();
        }

        return (bool) $candidate;
    }

    private function stringValue(?string $candidate, string $fallback): string
    {
        $trimmed = trim($candidate ?? '');

        return $trimmed !== '' ? $trimmed : $fallback;
    }
}
