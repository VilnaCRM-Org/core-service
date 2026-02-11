<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext\Manager;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\ValueObject\Ulid;

final readonly class EntityManager
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private StatusRepositoryInterface $statusRepository,
        private TypeRepositoryInterface $typeRepository
    ) {
    }

    public function saveCustomer(Customer $customer): void
    {
        $this->customerRepository->save($customer);
    }

    public function saveType(CustomerType $type): void
    {
        $this->typeRepository->save($type);
    }

    public function saveStatus(CustomerStatus $status): void
    {
        $this->statusRepository->save($status);
    }

    public function findType(Ulid $id): ?CustomerType
    {
        return $this->typeRepository->find($id);
    }

    public function findStatus(Ulid $id): ?CustomerStatus
    {
        return $this->statusRepository->find($id);
    }

    public function findCustomer(mixed $id): ?Customer
    {
        return $this->customerRepository->find($id);
    }

    public function findCustomerByEmail(string $email): ?Customer
    {
        return $this->customerRepository->findByEmail($email);
    }

    public function deleteCustomer(Customer $customer): void
    {
        $this->customerRepository->delete($customer);
    }

    public function deleteCustomerByEmail(string $email): void
    {
        $this->customerRepository->deleteByEmail($email);
    }

    public function deleteType(CustomerType $type): void
    {
        $this->typeRepository->delete($type);
    }

    public function deleteStatus(CustomerStatus $status): void
    {
        $this->statusRepository->delete($status);
    }

    public function deleteTypeByValue(string $value): void
    {
        $this->typeRepository->deleteByValue($value);
    }

    public function deleteStatusByValue(string $value): void
    {
        $this->statusRepository->deleteByValue($value);
    }
}
