<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerInterface;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;

/**
 * Test helper class for testing __call() proxy behavior
 */
final class CustomerRepositoryTestHelper implements CustomerRepositoryInterface
{
    public function __construct(
        private CustomerRepositoryInterface $inner
    ) {
    }

    public function save(Customer $customer): void
    {
        $this->inner->save($customer);
    }

    public function findByEmail(string $email): ?CustomerInterface
    {
        return $this->inner->findByEmail($email);
    }

    public function find(mixed $id, int $lockMode = 0, ?int $lockVersion = null): ?object
    {
        return $this->inner->find($id, $lockMode, $lockVersion);
    }

    public function delete(Customer $customer): void
    {
        $this->inner->delete($customer);
    }

    public function deleteByEmail(string $email): void
    {
        $this->inner->deleteByEmail($email);
    }

    /**
     * Additional method not in the interface - for testing __call() proxy
     */
    public function getClassName(): string
    {
        return Customer::class;
    }
}
