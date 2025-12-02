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
        return array_reduce(
            array_keys(self::STRING_FIELDS),
            fn (array $result, string $field): array => array_merge($result, [
                $field => $this->stringValue(
                    $input[$field] ?? null,
                    $customer->{self::STRING_FIELDS[$field]}()
                ),
            ]),
            []
        );
    }

    /**
     * @param array{
     *     confirmed?: bool|int|string|null
     * } $input
     */
    public function resolveConfirmed(Customer $customer, array $input): bool
    {
        $candidate = $input['confirmed'] ?? null;

        return $candidate === null ? $customer->isConfirmed() : (bool) $candidate;
    }

    private function stringValue(?string $candidate, string $fallback): string
    {
        $trimmed = trim($candidate ?? '');

        // Pattern: Match expression for cleaner logic
        return match (true) {
            $trimmed !== '' => $trimmed,
            default => $fallback,
        };
    }
}
