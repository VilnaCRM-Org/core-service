<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Factory\UlidFactory;

final readonly class SchemathesisCustomerSeeder
{
    private const CUSTOMER_DEFINITIONS = [
        'primary' => [
            'id' => SchemathesisFixtures::CUSTOMER_ID,
            'email' => SchemathesisFixtures::CUSTOMER_EMAIL,
            'initials' => SchemathesisFixtures::CUSTOMER_INITIALS,
            'phone' => SchemathesisFixtures::CUSTOMER_PHONE,
            'leadSource' => SchemathesisFixtures::CUSTOMER_LEAD_SOURCE,
            'confirmed' => false,
        ],
        'update' => [
            'id' => SchemathesisFixtures::UPDATE_CUSTOMER_ID,
            'email' => SchemathesisFixtures::UPDATE_CUSTOMER_EMAIL,
            'initials' => SchemathesisFixtures::UPDATE_CUSTOMER_INITIALS,
            'phone' => SchemathesisFixtures::UPDATE_CUSTOMER_PHONE,
            'leadSource' => SchemathesisFixtures::UPDATE_CUSTOMER_LEAD_SOURCE,
            'confirmed' => false,
        ],
        'delete' => [
            'id' => SchemathesisFixtures::DELETE_CUSTOMER_ID,
            'email' => SchemathesisFixtures::DELETE_CUSTOMER_EMAIL,
            'initials' => SchemathesisFixtures::DELETE_CUSTOMER_INITIALS,
            'phone' => SchemathesisFixtures::DELETE_CUSTOMER_PHONE,
            'leadSource' => SchemathesisFixtures::DELETE_CUSTOMER_LEAD_SOURCE,
            'confirmed' => true,
        ],
        'replace' => [
            'id' => SchemathesisFixtures::REPLACE_CUSTOMER_ID,
            'email' => SchemathesisFixtures::REPLACE_CUSTOMER_EMAIL,
            'initials' => SchemathesisFixtures::REPLACE_CUSTOMER_INITIALS,
            'phone' => SchemathesisFixtures::REPLACE_CUSTOMER_PHONE,
            'leadSource' => SchemathesisFixtures::REPLACE_CUSTOMER_LEAD_SOURCE,
            'confirmed' => false,
        ],
        'get' => [
            'id' => SchemathesisFixtures::GET_CUSTOMER_ID,
            'email' => SchemathesisFixtures::GET_CUSTOMER_EMAIL,
            'initials' => SchemathesisFixtures::GET_CUSTOMER_INITIALS,
            'phone' => SchemathesisFixtures::GET_CUSTOMER_PHONE,
            'leadSource' => SchemathesisFixtures::GET_CUSTOMER_LEAD_SOURCE,
            'confirmed' => true,
        ],
    ];

    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private UlidFactory $ulidFactory
    ) {
    }

    /**
     * @param array<string,CustomerType> $types
     * @param array<string,CustomerStatus> $statuses
     *
     * @return array<string,CustomerInterface>
     */
    public function seedCustomers(array $types, array $statuses): array
    {
        $results = [];
        $defaultType = $types['default'];
        $defaultStatus = $statuses['default'];

        foreach (self::CUSTOMER_DEFINITIONS as $key => $definition) {
            $results[$key] = $this->seedCustomer(
                $definition,
                $defaultType,
                $defaultStatus
            );
        }

        return $results;
    }

    /**
     * @param array{id: string, email: string, initials: string, phone: string, leadSource: string, confirmed: bool} $definition
     */
    private function seedCustomer(
        array $definition,
        CustomerType $type,
        CustomerStatus $status
    ): CustomerInterface {
        $customer = $this->customerRepository->find($definition['id']);

        if ($customer === null) {
            return $this->createCustomer($definition, $type, $status);
        }

        $this->updateCustomer($customer, $definition, $type, $status);

        return $customer;
    }

    /**
     * @param array{id: string, email: string, initials: string, phone: string, leadSource: string, confirmed: bool} $definition
     */
    private function createCustomer(
        array $definition,
        CustomerType $type,
        CustomerStatus $status
    ): CustomerInterface {
        $ulid = $this->ulidFactory->create($definition['id']);

        $customer = new Customer(
            $definition['initials'],
            $definition['email'],
            $definition['phone'],
            $definition['leadSource'],
            $type,
            $status,
            $definition['confirmed'],
            $ulid
        );

        $this->customerRepository->save($customer);

        return $customer;
    }

    /**
     * @param array{id: string, email: string, initials: string, phone: string, leadSource: string, confirmed: bool} $definition
     */
    private function updateCustomer(
        CustomerInterface $customer,
        array $definition,
        CustomerType $type,
        CustomerStatus $status
    ): void {
        $customer->setEmail($definition['email']);
        $customer->setInitials($definition['initials']);
        $customer->setPhone($definition['phone']);
        $customer->setLeadSource($definition['leadSource']);
        $customer->setType($type);
        $customer->setStatus($status);
        $customer->setConfirmed($definition['confirmed']);

        $this->customerRepository->save($customer);
    }
}
