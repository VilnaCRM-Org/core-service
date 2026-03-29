<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheTagResolver;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Cached Customer Repository Decorator
 *
 * Responsibilities:
 * - Read-through caching with Stale-While-Revalidate (SWR)
 * - Cache key management via CacheKeyBuilder
 * - Cache hit/miss logging for observability
 * - Graceful fallback to database on cache errors
 * - Delegates ALL persistence operations to inner repository
 *
 * Decorator Pattern:
 * - Wraps MongoCustomerRepository
 * - Adds caching layer without modifying persistence logic
 * - Transparent to consumers (implements same interface)
 *
 * Cache Invalidation:
 * - Handled by CustomerCacheInvalidationSubscriber via domain events
 * - Direct deleteByEmail/deleteById cleanup paths invalidate cache explicitly
 */
final class CachedCustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private CustomerRepositoryInterface $inner,
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private CustomerCacheTagResolver $cacheTagResolver,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Proxy all other method calls to inner repository
     *
     * This ensures compatibility with API Platform's collection provider
     * which may call Doctrine repository methods not in our interface.
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->inner->{$method}(...$arguments);
    }

    /**
     * Cache Policy: find by ID
     *
     * Key Pattern: customer.{id}
     * TTL: 600s (10 minutes)
     * Consistency: Stale-While-Revalidate (beta: 1.0)
     * Invalidation: Via CustomerCacheInvalidationSubscriber on update/delete
     * Tags: [customer, customer.{id}]
     * Notes: Read-heavy operation, tolerates brief staleness
     */
    public function find(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?Customer {
        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey((string) $id);

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
                beta: 1.0  // Enable Stale-While-Revalidate
            );
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);

            // Graceful fallback to database on cache failure
            return $this->inner->find($id, $lockMode, $lockVersion);
        }
    }

    /**
     * Cache Policy: findByEmail
     *
     * Key Pattern: customer.email.{hash}
     * TTL: 300s (5 minutes)
     * Consistency: Eventual
     * Invalidation: Via CustomerCacheInvalidationSubscriber on email change
     * Tags: [customer, customer.email, customer.email.{hash}]
     * Notes: Common authentication/lookup operation
     */
    public function findByEmail(string $email): ?Customer
    {
        $cacheKey = $this->cacheKeyBuilder->buildCustomerEmailKey($email);

        try {
            return $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadCustomerByEmail(
                    $email,
                    $cacheKey,
                    $item
                )
            );
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);

            return $this->inner->findByEmail($email);
        }
    }

    /**
     * Delegate persistence to inner repository (no caching on writes)
     */
    public function save(Customer $customer): void
    {
        $this->inner->save($customer);
    }

    /**
     * Delegate deletion to inner repository (no invalidation here)
     *
     * Cache invalidation is handled via CustomerDeletedEvent subscribers.
     */
    public function delete(Customer $customer): void
    {
        $this->inner->delete($customer);
    }

    /**
     * Direct deletion by email bypasses domain events, so cache invalidation
     * must happen here to keep test cleanup and other raw-delete callers
     * consistent with the normal event-driven delete path.
     */
    public function deleteByEmail(string $email): void
    {
        $customer = $this->findCustomerForDeleteByEmail($email);

        $this->inner->deleteByEmail($email);

        $this->invalidateTagsForDeletedCustomer($customer, $email);
    }

    /**
     * Direct deletion by ID bypasses domain events, so cache invalidation
     * must happen here to keep test cleanup and other raw-delete callers
     * consistent with the normal event-driven delete path.
     */
    public function deleteById(mixed $id): void
    {
        $customer = $this->findCustomerForDeleteById($id);

        $this->inner->deleteById($id);

        $this->invalidateTagsForDeletedCustomer(
            $customer,
            null,
            (string) $id
        );
    }

    /**
     * Load customer from database and configure cache item
     */
    private function loadCustomerFromDb(
        mixed $id,
        int $lockMode,
        ?int $lockVersion,
        string $cacheKey,
        ItemInterface $item
    ): ?Customer {
        $item->expiresAfter(600);  // 10 minutes TTL
        $item->tag(['customer', "customer.{$id}"]);

        $this->logger->info('Cache miss - loading customer from database', [
            'cache_key' => $cacheKey,
            'customer_id' => $id,
            'operation' => 'cache.miss',
        ]);

        return $this->inner->find($id, $lockMode, $lockVersion);
    }

    /**
     * Load customer by email from database and configure cache item
     */
    private function loadCustomerByEmail(
        string $email,
        string $cacheKey,
        ItemInterface $item
    ): ?Customer {
        $item->expiresAfter(300);  // 5 minutes TTL
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $item->tag([
            'customer',
            'customer.email',
            "customer.email.{$emailHash}",
        ]);

        $this->logger->info('Cache miss - loading customer by email', [
            'cache_key' => $cacheKey,
            'operation' => 'cache.miss',
        ]);

        return $this->inner->findByEmail($email);
    }

    /**
     * Log cache errors for observability
     */
    private function logCacheError(string $cacheKey, \Throwable $e): void
    {
        $this->logger->error('Cache error - falling back to database', [
            'cache_key' => $cacheKey,
            'error' => $e->getMessage(),
            'operation' => 'cache.error',
        ]);
    }

    private function findCustomerForDeleteByEmail(string $email): ?Customer
    {
        try {
            return $this->inner->findByEmail($email);
        } catch (\Throwable $e) {
            $this->logger->warning('Customer lookup failed before deleteByEmail', [
                'operation' => 'customer.delete.lookup_failed',
                'email_hash' => $this->cacheKeyBuilder->hashEmail($email),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function findCustomerForDeleteById(mixed $id): ?Customer
    {
        try {
            return $this->inner->find($id);
        } catch (\Throwable $e) {
            $this->logger->warning('Customer lookup failed before deleteById', [
                'operation' => 'customer.delete.lookup_failed',
                'customer_id_hash' => hash('sha256', (string) $id),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function invalidateTagsForDeletedCustomer(
        ?Customer $customer,
        ?string $deletedEmail = null,
        ?string $deletedId = null
    ): void {
        $tags = $this->cacheTagResolver->resolveForDeletedCustomer(
            $customer,
            $deletedEmail,
            $deletedId
        );

        try {
            $this->cache->invalidateTags(iterator_to_array($tags));
        } catch (\Throwable $e) {
            $this->logger->warning('Cache invalidation failed after customer deletion', [
                'operation' => 'cache.invalidation.error',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
