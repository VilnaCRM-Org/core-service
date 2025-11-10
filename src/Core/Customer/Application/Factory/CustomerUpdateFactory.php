<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Application\Service\StringFieldResolver;

final readonly class CustomerUpdateFactory implements
    CustomerUpdateFactoryInterface
{
    public function __construct(
        private CustomerRelationTransformerInterface $relationResolver,
        private StringFieldResolver $fieldResolver,
    ) {
    }

    /**
     * @param array{
     *     initials?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     leadSource?: string|null,
     *     type?: string|null,
     *     status?: string|null,
     *     confirmed?: bool|null
     * } $input
     */
    public function create(Customer $customer, array $input): CustomerUpdate
    {
        $fields = $this->resolveStringFields($input, $customer);

        return new CustomerUpdate(...$fields, ...$this->resolveRelations($input, $customer));
    }

    /**
     * @param array<string, string|bool|null> $input
     *
     * @return array{newInitials: string, newEmail: string, newPhone: string, newLeadSource: string}
     */
    private function resolveStringFields(array $input, Customer $customer): array
    {
        $fields = [
            'initials' => ['key' => 'newInitials', 'getter' => 'getInitials'],
            'email' => ['key' => 'newEmail', 'getter' => 'getEmail'],
            'phone' => ['key' => 'newPhone', 'getter' => 'getPhone'],
            'leadSource' => ['key' => 'newLeadSource', 'getter' => 'getLeadSource'],
        ];

        return array_reduce(
            array_keys($fields),
            fn (array $result, string $field) => array_merge($result, [
                $fields[$field]['key'] => $this->fieldResolver->resolve(
                    $input[$field] ?? null,
                    $customer->{$fields[$field]['getter']}()
                ),
            ]),
            []
        );
    }

    /**
     * @param array<string, string|bool|null> $input
     *
     * @return array{
     *     newType: \App\Core\Customer\Domain\Entity\CustomerType,
     *     newStatus: \App\Core\Customer\Domain\Entity\CustomerStatus,
     *     newConfirmed: bool
     * }
     */
    private function resolveRelations(array $input, Customer $customer): array
    {
        return [
            'newType' => $this->relationResolver->resolveType(
                $input['type'] ?? null,
                $customer
            ),
            'newStatus' => $this->relationResolver->resolveStatus(
                $input['status'] ?? null,
                $customer
            ),
            'newConfirmed' => $input['confirmed'] ?? $customer->isConfirmed(),
        ];
    }
}
