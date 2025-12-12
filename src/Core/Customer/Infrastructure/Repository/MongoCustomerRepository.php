<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Customer Repository with Production-Grade Caching
 *
 * Cache Policies:
 * - find($id): TTL 600s, SWR, tag-based invalidation
 * - findByEmail: TTL 300s, invalidate on email change
 *
 * @extends BaseRepository<Customer>
 */
final class MongoCustomerRepository extends BaseRepository implements
    CustomerRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly TagAwareCacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($registry, Customer::class);
        $this->documentManager = $this->getDocumentManager();
    }

    /**
     * Cache Policy: find by ID
     *
     * Key Pattern: customer.{id}
     * TTL: 600s (10 minutes)
     * Consistency: Stale-While-Revalidate
     * Invalidation: On customer update/delete
     * Tags: [customer, customer.{id}]
     * Notes: Read-heavy operation, tolerates brief staleness
     */
    public function find(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object {
        $cacheKey = $this->buildCacheKey('customer', (string) $id);

        try {
            return $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadCustomerFromDb(
                    $id,
                    $lockMode,
                    $lockVersion,
                    $cacheKey,
                    $item
                ),
                beta: 1.0
            );
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);

            return $this->documentManager->find(Customer::class, $id, $lockMode, $lockVersion);
        }
    }

    /**
     * Cache Policy: findByEmail
     *
     * Key Pattern: customer.email.{hash}
     * TTL: 300s (5 minutes)
     * Consistency: Eventual
     * Invalidation: On customer update (if email changed)
     * Tags: [customer, customer.email, customer.email.{hash}]
     * Notes: Common authentication/lookup operation
     */
    public function findByEmail(string $email): ?Customer
    {
        $emailHash = hash('sha256', strtolower($email));
        $cacheKey = $this->buildCacheKey('customer', 'email', $emailHash);

        try {
            return $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadCustomerByEmail(
                    $email,
                    $emailHash,
                    $cacheKey,
                    $item
                )
            );
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);

            return $this->findOneByCriteria(['email' => $email]);
        }
    }

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

    /**
     * Build cache key from parts
     */
    private function buildCacheKey(string $prefix, string ...$parts): string
    {
        return $prefix . '.' . implode('.', $parts);
    }

    private function loadCustomerByEmail(
        string $email,
        string $emailHash,
        string $cacheKey,
        ItemInterface $item
    ): ?Customer {
        $item->expiresAfter(300);
        $item->tag([
            'customer',
            'customer.email',
            "customer.email.{$emailHash}",
        ]);

        $this->logger->info('Cache miss - loading customer by email', [
            'cache_key' => $cacheKey,
            'operation' => 'cache.miss',
        ]);

        return $this->findOneByCriteria(['email' => $email]);
    }

    private function loadCustomerFromDb(
        mixed $id,
        int $lockMode,
        ?int $lockVersion,
        string $cacheKey,
        ItemInterface $item
    ): ?object {
        $item->expiresAfter(600);
        $item->tag(['customer', "customer.{$id}"]);

        $this->logger->info('Cache miss - loading customer from database', [
            'cache_key' => $cacheKey,
            'customer_id' => $id,
            'operation' => 'cache.miss',
        ]);

        return $this->documentManager->find(Customer::class, $id, $lockMode, $lockVersion);
    }

    private function logCacheError(string $cacheKey, \Throwable $e): void
    {
        $this->logger->error('Cache error - falling back to database', [
            'cache_key' => $cacheKey,
            'error' => $e->getMessage(),
        ]);
    }
}
