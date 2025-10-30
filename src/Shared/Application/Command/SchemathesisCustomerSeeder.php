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
                $definition['id'],
                $definition['email'],
                $definition['initials'],
                $definition['phone'],
                $definition['leadSource'],
                $defaultType,
                $defaultStatus,
                $definition['confirmed']
            );
        }

        return $results;
    }

    private function seedCustomer(
        string $id,
        string $email,
        string $initials,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed
    ): CustomerInterface {
        $customer = $this->customerRepository->find($id);

        if ($customer === null) {
            $customer = $this->createCustomer(
                $id,
                $email,
                $initials,
                $phone,
                $leadSource,
                $type,
                $status,
                $confirmed
            );
        } else {
            $this->updateCustomer(
                $customer,
                $email,
                $initials,
                $phone,
                $leadSource,
                $type,
                $status,
                $confirmed
            );
        }

        return $customer;
    }

    private function createCustomer(
        string $id,
        string $email,
        string $initials,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed
    ): CustomerInterface {
        $ulid = $this->ulidFactory->create($id);

        $customer = new Customer(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed,
            $ulid
        );

        $this->customerRepository->save($customer);

        return $customer;
    }

    private function updateCustomer(
        CustomerInterface $customer,
        string $email,
        string $initials,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed
    ): void {
        $customer->setEmail($email);
        $customer->setInitials($initials);
        $customer->setPhone($phone);
        $customer->setLeadSource($leadSource);
        $customer->setType($type);
        $customer->setStatus($status);
        $customer->setConfirmed($confirmed);

        $this->customerRepository->save($customer);
    }
}
