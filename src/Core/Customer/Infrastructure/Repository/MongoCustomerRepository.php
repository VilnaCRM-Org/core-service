<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerStreamRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use InvalidArgumentException;

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
 * - Implements CustomerStreamRepositoryInterface
 *
 * @extends BaseRepository<Customer>
 */
final class MongoCustomerRepository extends BaseRepository implements
    CustomerStreamRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
        $this->documentManager = $this->getDocumentManager();
    }

    /**
     * Find customer by email (database only, no caching)
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->findOneByCriteria(['email' => strtolower($email)]);
    }

    /**
     * Stream every customer through a MongoDB cursor.
     *
     * The query builder cursor hydrates documents lazily, so the backfill
     * command never holds the full collection in memory at once.
     *
     * @return iterable<Customer>
     */
    public function findAllIterable(): iterable
    {
        yield from $this->createQueryBuilder()
            ->getQuery()
            ->getIterator();
    }

    public function findFresh(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object {
        return $this->find($id, $lockMode, $lockVersion);
    }

    /**
     * Delete customer with proper entity management
     */
    public function delete(object $entity): void
    {
        if (! $entity instanceof Customer) {
            throw new InvalidArgumentException('Expected Customer instance.');
        }

        $managedCustomer = $this->documentManager->contains($entity)
            ? $entity
            : $this->documentManager->merge($entity);

        parent::delete($managedCustomer);
    }

    public function deleteByEmail(string $email): void
    {
        $customer = $this->findByEmail($email);

        if (! $customer instanceof Customer) {
            return;
        }

        $this->delete($customer);
    }

    public function deleteById(mixed $id): void
    {
        $customer = $this->find($id);

        if (! $customer instanceof Customer) {
            return;
        }

        $this->delete($customer);
    }
}
