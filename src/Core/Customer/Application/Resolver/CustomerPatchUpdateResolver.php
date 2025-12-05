<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;

final readonly class CustomerPatchUpdateResolver
{
    public function __construct(
        private CustomerUpdateScalarResolver $scalarResolver,
        private CustomerRelationTransformerInterface $relationTransformer
    ) {
    }

    public function build(CustomerPatch $data, Customer $customer): CustomerUpdate
    {
        $strings = $this->scalarResolver->resolveStrings(
            $customer,
            [
                'initials' => $data->initials,
                'email' => $data->email,
                'phone' => $data->phone,
                'leadSource' => $data->leadSource,
            ]
        );

        return $this->createUpdate($strings, $data, $customer);
    }

    /**
     * @param array{
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     leadSource: string
     * } $strings
     */
    private function createUpdate(
        array $strings,
        CustomerPatch $data,
        Customer $customer
    ): CustomerUpdate {
        return new CustomerUpdate(
            $strings['initials'],
            $strings['email'],
            $strings['phone'],
            $strings['leadSource'],
            $this->relationTransformer->resolveType($data->type, $customer),
            $this->relationTransformer->resolveStatus($data->status, $customer),
            $this->scalarResolver->resolveConfirmed(
                $customer,
                ['confirmed' => $data->confirmed]
            )
        );
    }
}
