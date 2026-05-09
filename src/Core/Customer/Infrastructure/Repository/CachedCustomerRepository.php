<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheTagResolver;
use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Throwable;

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
 * - Managed deletes are owned by the shared ODM invalidation path
 * - Direct deleteByEmail/deleteById fallback tags are used only on lookup misses
 */
final class CachedCustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private CustomerRepositoryInterface $inner,
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private CustomerCacheTagResolver $cacheTagResolver,
        private LoggerInterface $logger,
        private CustomerCachePolicyCollection $cachePolicies,
        private ?CacheInvalidationCommandHandler $invalidationHandler = null
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
        $policy = $this->cachePolicies()->detail();
        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey((string) $id);
        $cacheMiss = false;

        try {
            return $this->findCachedCustomerWithFallback(
                $cacheKey,
                $id,
                $lockMode,
                $lockVersion,
                $policy,
                $cacheMiss
            );
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);

            // Graceful fallback to database on cache failure
            return $this->inner->find($id, $lockMode, $lockVersion);
        }
    }

    public function findFresh(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object {
        return $this->inner->find($id, $lockMode, $lockVersion);
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
        $policy = $this->cachePolicies()->lookup();
        $cacheKey = $this->cacheKeyBuilder->buildCustomerEmailKey($email);
        $cacheMiss = false;

        try {
            $customer = $this->findCachedCustomerByEmail(
                $cacheKey,
                $policy,
                $email,
                $cacheMiss
            );
            $this->logHitWhenLoadedFromCache(
                $cacheMiss,
                $cacheKey,
                CustomerCachePolicyCollection::FAMILY_LOOKUP
            );

            return $customer;
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
     * Managed delete invalidation is handled by the shared ODM listener.
     */
    public function delete(Customer $customer): void
    {
        $this->inner->delete($customer);
    }

    /**
     * Direct deletion by email is covered by the ODM listener when the customer
     * can be loaded as a managed document. Fallback tag invalidation remains for
     * lookup misses or lookup failures where the raw delete path may bypass that.
     */
    public function deleteByEmail(string $email): void
    {
        $customer = $this->findCustomerForDeleteByEmail($email);

        if ($customer instanceof Customer) {
            $this->inner->delete($customer);

            return;
        }

        $this->inner->deleteByEmail($email);
        $this->invalidateTagsForDeletedCustomer($customer, $email);
    }

    /**
     * Direct deletion by ID is covered by the ODM listener when the customer can
     * be loaded as a managed document. Fallback tag invalidation remains for
     * lookup misses or lookup failures where the raw delete path may bypass that.
     */
    public function deleteById(mixed $id): void
    {
        $customer = $this->findCustomerForDeleteById($id);

        if ($customer instanceof Customer) {
            $this->inner->delete($customer);

            return;
        }

        $this->inner->deleteById($id);
        $this->invalidateTagsForDeletedCustomer(
            $customer,
            null,
            (string) $id
        );
    }

    /**
     * Load customer from database and configure cache item
     *
     * @param array{ttl: int, tags: list<string>} $policy
     */
    private function findCachedCustomerWithFallback(
        string $cacheKey,
        mixed $id,
        int $lockMode,
        ?int $lockVersion,
        array $policy,
        bool &$cacheMiss
    ): ?Customer {
        $customer = $this->findCachedCustomer(
            $cacheKey,
            $policy,
            $id,
            $lockMode,
            $lockVersion,
            $cacheMiss
        );
        $this->logHitWhenLoadedFromCache(
            $cacheMiss,
            $cacheKey,
            CustomerCachePolicyCollection::FAMILY_DETAIL
        );

        return $customer;
    }

    /**
     * @param array{ttl: int, tags: list<string>} $policy
     */
    private function findCachedCustomer(
        string $cacheKey,
        array $policy,
        mixed $id,
        int $lockMode,
        ?int $lockVersion,
        bool &$cacheMiss
    ): ?Customer {
        return $this->cache->get(
            $cacheKey,
            $this->loadCustomerFromDbCallback(
                $id,
                $lockMode,
                $lockVersion,
                $cacheKey,
                $policy,
                $cacheMiss
            ),
            beta: $this->cachePolicies()->beta($policy)
        );
    }

    /**
     * @param array{ttl: int, tags: list<string>} $policy
     *
     * @return callable(ItemInterface): ?Customer
     */
    private function loadCustomerFromDbCallback(
        mixed $id,
        int $lockMode,
        ?int $lockVersion,
        string $cacheKey,
        array $policy,
        bool &$cacheMiss
    ): callable {
        return function (ItemInterface $item) use (
            $id,
            $lockMode,
            $lockVersion,
            $cacheKey,
            $policy,
            &$cacheMiss
        ): ?Customer {
            $cacheMiss = true;

            return $this->loadCustomerFromDb(
                $id,
                $lockMode,
                $lockVersion,
                $cacheKey,
                $item,
                $policy
            );
        };
    }

    /**
     * Load customer from database and configure cache item
     *
     * @param array{ttl: int, tags: list<string>} $policy
     */
    private function loadCustomerFromDb(
        mixed $id,
        int $lockMode,
        ?int $lockVersion,
        string $cacheKey,
        ItemInterface $item,
        array $policy
    ): ?Customer {
        $item->expiresAfter($this->cachePolicies()->ttl($policy));
        $item->tag($this->cachePolicies()->tags($policy, "customer.{$id}"));

        $this->logger->info('Cache miss - loading customer from database', [
            'cache_key' => $cacheKey,
            'customer_id' => $id,
            'operation' => 'cache.miss',
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_DETAIL,
        ]);

        return $this->inner->find($id, $lockMode, $lockVersion);
    }

    /**
     * Load customer by email from database and configure cache item
     *
     * @param array{ttl: int, tags: list<string>} $policy
     */
    private function findCachedCustomerByEmail(
        string $cacheKey,
        array $policy,
        string $email,
        bool &$cacheMiss
    ): ?Customer {
        $loader = function (ItemInterface $item) use (
            $email,
            $cacheKey,
            $policy,
            &$cacheMiss
        ): ?Customer {
            $cacheMiss = true;

            return $this->loadCustomerByEmail(
                $email,
                $cacheKey,
                $item,
                $policy
            );
        };

        return $this->cache->get(
            $cacheKey,
            $loader,
            $this->cachePolicies()->beta($policy)
        );
    }

    /**
     * Load customer by email from database and configure cache item
     *
     * @param array{ttl: int, tags: list<string>} $policy
     */
    private function loadCustomerByEmail(
        string $email,
        string $cacheKey,
        ItemInterface $item,
        array $policy
    ): ?Customer {
        $customer = $this->inner->findByEmail($email);
        $effectivePolicy = $customer instanceof Customer
            ? $policy
            : $this->cachePolicies()->forFamily(
                CustomerCachePolicyCollection::FAMILY_NEGATIVE_LOOKUP
            );

        $item->expiresAfter($this->cachePolicies()->ttl($effectivePolicy));
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $item->tag($this->cachePolicies()->tags(
            $effectivePolicy,
            "customer.email.{$emailHash}"
        ));

        $this->logger->info('Cache miss - loading customer by email', [
            'cache_key' => $cacheKey,
            'operation' => 'cache.miss',
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => $effectivePolicy['family'],
        ]);

        return $customer;
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

    private function logCacheHit(string $cacheKey, string $family): void
    {
        $this->logger->info('Cache hit - returning customer from cache', [
            'cache_key' => $cacheKey,
            'operation' => 'cache.hit',
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => $family,
        ]);
    }

    private function logHitWhenLoadedFromCache(
        bool $cacheMiss,
        string $cacheKey,
        string $family
    ): void {
        if ($cacheMiss) {
            return;
        }

        $this->logCacheHit($cacheKey, $family);
    }

    private function cachePolicies(): CustomerCachePolicyCollection
    {
        return $this->cachePolicies;
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
        try {
            $tags = iterator_to_array($this->cacheTagResolver->resolveForDeletedCustomer(
                $customer,
                $deletedEmail,
                $deletedId
            ));

            if ($this->invalidateTagsThroughSharedHandler($tags)) {
                return;
            }

            if ($this->cache->invalidateTags($tags) === true) {
                return;
            }

            $this->logCacheInvalidationFailure('Tag invalidation returned false');
        } catch (\Throwable $e) {
            $this->logCacheInvalidationFailure($e->getMessage());
        }
    }

    private function logCacheInvalidationFailure(string $error): void
    {
        $this->logger->warning('Cache invalidation failed after customer deletion', [
            'operation' => 'cache.invalidation.error',
            'error' => $error,
        ]);
    }

    /**
     * @param list<string> $tags
     */
    private function invalidateTagsThroughSharedHandler(array $tags): bool
    {
        if (! $this->invalidationHandler instanceof CacheInvalidationCommandHandler) {
            return false;
        }

        try {
            return $this->dispatchRepositoryFallbackInvalidation(
                $this->invalidationHandler,
                $tags
            );
        } catch (Throwable $e) {
            $this->logger->warning('Shared cache invalidation failed after customer deletion', [
                'operation' => 'cache.invalidation.error',
                'source' => 'repository_fallback',
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * @param list<string> $tags
     */
    private function dispatchRepositoryFallbackInvalidation(
        CacheInvalidationCommandHandler $invalidationHandler,
        array $tags
    ): bool {
        return $invalidationHandler->tryHandle(CacheInvalidationCommand::create(
            CustomerCachePolicyCollection::CONTEXT,
            'repository_fallback',
            'deleted',
            CacheInvalidationTagSet::create(...$tags),
            CacheRefreshCommandCollection::create()
        ));
    }
}
