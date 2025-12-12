<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * MongoDB Customer Repository
 *
 * Responsibilities:
 * - Database persistence operations ONLY
 * - NO caching logic (handled by CachedCustomerRepository decorator)
 *
 * Design:
 * - Focused on single responsibility (persistence)
 * - Wrapped by CachedCustomerRepository for caching
 * - Implements CustomerRepositoryInterface
 *
 * @extends BaseRepository<Customer>
 */
final class MongoCustomerRepository extends BaseRepository implements
    CustomerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
        $this->documentManager = $this->getDocumentManager();
    }

    /**
     * Find customer by ID (database only, no caching)
     */
    public function find(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object {
        return $this->documentManager->find(Customer::class, $id, $lockMode, $lockVersion);
    }

    /**
     * Find customer by email (database only, no caching)
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->findOneByCriteria(['email' => $email]);
    }

    /**
     * Delete customer with proper entity management
     */
    public function delete(object $entity): void
    {
        if (!$entity instanceof Customer) {
            parent::delete($entity);

            return;
        }

        $managedCustomer = $this->documentManager->contains($entity)
            ? $entity
            : $this->documentManager->merge($entity);

        parent::delete($managedCustomer);
    }
}
