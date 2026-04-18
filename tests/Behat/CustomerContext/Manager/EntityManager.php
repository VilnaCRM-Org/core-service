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
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class EntityManager
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private StatusRepositoryInterface $statusRepository,
        private TypeRepositoryInterface $typeRepository,
        private TagAwareCacheInterface $customerCache
    ) {
    }

    public function saveCustomer(Customer $customer): void
    {
        $this->customerRepository->save($customer);
        $this->invalidateCustomerCache();
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

    public function deleteCustomer(Customer $customer): void
    {
        $this->customerRepository->delete($customer);
        $this->invalidateCustomerCache();
    }

    public function deleteCustomerByEmail(string $email): void
    {
        $this->customerRepository->deleteByEmail($email);
        $this->invalidateCustomerCache();
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

    /**
     * Direct Behat fixture writes bypass the normal event-driven cache invalidation path.
     */
    private function invalidateCustomerCache(): void
    {
        $this->customerCache->invalidateTags(['customer']);
    }
}
