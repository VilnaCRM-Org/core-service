<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheRefreshCommandHandlerBase;
use App\Shared\Application\DTO\CacheRefreshResult;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Throwable;

/**
 * @psalm-suppress UnusedClass Wired through the app.cache_refresh_handler service tag.
 */
final class CustomerCacheRefreshCommandHandler extends CacheRefreshCommandHandlerBase
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private CacheKeyBuilder $cacheKeyBuilder,
        private CustomerCachePolicyCollection $policies,
        private LoggerInterface $logger,
        private ?TagAwareCacheInterface $cache = null
    ) {
    }

    public function context(): string
    {
        return CustomerCachePolicyCollection::CONTEXT;
    }

    protected function refresh(CacheRefreshCommand $command): CacheRefreshResult
    {
        if ($this->cache === null) {
            return $this->skipUnavailableCache($command);
        }

        $cache = $this->cache;

        try {
            return match ($command->family()) {
                CustomerCachePolicyCollection::FAMILY_DETAIL => $this->refreshDetail(
                    $command,
                    $cache
                ),
                CustomerCachePolicyCollection::FAMILY_LOOKUP => $this->refreshLookup(
                    $command,
                    $cache
                ),
                default => $this->skipUnsupportedFamily($command),
            };
        } catch (Throwable $e) {
            $this->logRefreshFailure($command, $e);

            throw $e;
        }
    }

    private function refreshDetail(
        CacheRefreshCommand $command,
        TagAwareCacheInterface $cache
    ): CacheRefreshResult {
        $customerId = $this->customerId($command);

        if ($customerId === null || $customerId === '') {
            return $this->skip($command, 'missing_customer_id');
        }

        $this->warmDetailCache($customerId, $cache);
        $this->logRefreshSuccess(
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            $command->dedupeKey()
        );

        return $this->success($command);
    }

    private function warmDetailCache(
        string $customerId,
        TagAwareCacheInterface $cache
    ): void {
        $policy = $this->policies()->detail();
        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey($customerId);
        $this->deleteCachedValue($cache, $cacheKey);
        $cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($customerId, $policy): ?object {
                $item->expiresAfter($this->policies()->ttl($policy));
                $item->tag($this->policies()->tags(
                    $policy,
                    'customer.' . $customerId
                ));

                return $this->repository->findFresh($customerId);
            },
            $this->policies->beta($policy)
        );
    }

    private function refreshLookup(
        CacheRefreshCommand $command,
        TagAwareCacheInterface $cache
    ): CacheRefreshResult {
        $email = $this->email($command);

        if ($email === null || $email === '') {
            return $this->skip($command, 'missing_email');
        }

        $this->warmLookupCache($email, $cache);
        $this->logRefreshSuccess(
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            $command->dedupeKey()
        );

        return $this->success($command);
    }

    private function warmLookupCache(
        string $email,
        TagAwareCacheInterface $cache
    ): void {
        $policy = $this->policies()->lookup();
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $cacheKey = $this->cacheKeyBuilder->buildCustomerEmailKey($email);
        $this->deleteCachedValue($cache, $cacheKey);
        $cache->get(
            $cacheKey,
            $this->lookupCacheLoader($email, $emailHash, $policy),
            $this->policies->beta($policy)
        );
    }

    /**
     * @param array{ttl: int, tags: list<string>} $policy
     *
     * @return callable(ItemInterface): ?Customer
     */
    private function lookupCacheLoader(
        string $email,
        string $emailHash,
        array $policy
    ): callable {
        return function (ItemInterface $item) use ($email, $emailHash, $policy): ?Customer {
            $customer = $this->repository->findByEmail($email);
            $effectivePolicy = $this->lookupPolicyForCustomer($customer, $policy);

            $item->expiresAfter($this->policies()->ttl($effectivePolicy));
            $item->tag($this->policies()->tags(
                $effectivePolicy,
                'customer.email.' . $emailHash
            ));

            return $customer;
        };
    }

    /**
     * @param array{ttl: int, tags: list<string>} $policy
     *
     * @return array{ttl: int, tags: list<string>}
     */
    private function lookupPolicyForCustomer(?Customer $customer, array $policy): array
    {
        if ($customer instanceof Customer) {
            return $policy;
        }

        return $this->policies()->forFamily(
            CustomerCachePolicyCollection::FAMILY_NEGATIVE_LOOKUP
        );
    }

    private function policies(): CustomerCachePolicyCollection
    {
        return $this->policies;
    }

    private function deleteCachedValue(TagAwareCacheInterface $cache, string $cacheKey): void
    {
        if ($cache->delete($cacheKey) === true) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Cache key "%s" could not be deleted before refresh.',
            $cacheKey
        ));
    }

    private function logRefreshSuccess(string $family, ?string $dedupeKey): void
    {
        $context = [
            'operation' => 'cache.refresh',
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => $family,
            'dedupe_key' => $dedupeKey,
        ];

        $this->logger->info('Customer cache refreshed', $context);
    }

    private function skipUnavailableCache(CacheRefreshCommand $command): CacheRefreshResult
    {
        $this->logger->warning('Customer cache refresh skipped without cache pool', [
            'operation' => 'cache.refresh.skipped',
            'family' => $command->family(),
        ]);

        return $this->skip($command, 'cache_unavailable');
    }

    private function skipUnsupportedFamily(CacheRefreshCommand $command): CacheRefreshResult
    {
        return $this->skip($command, 'unsupported_family');
    }

    private function skip(CacheRefreshCommand $command, string $reason): CacheRefreshResult
    {
        return CacheRefreshResult::skipped(
            $command->context(),
            $command->family(),
            $command->dedupeKey(),
            $reason
        );
    }

    private function success(CacheRefreshCommand $command): CacheRefreshResult
    {
        return CacheRefreshResult::success(
            $command->context(),
            $command->family(),
            $command->dedupeKey()
        );
    }

    private function customerId(CacheRefreshCommand $command): ?string
    {
        return $command->identifierName() === 'customer_id'
            ? $command->identifierValue()
            : null;
    }

    private function email(CacheRefreshCommand $command): ?string
    {
        return $command->identifierName() === 'email'
            ? $command->identifierValue()
            : null;
    }

    private function logRefreshFailure(CacheRefreshCommand $command, Throwable $e): void
    {
        $this->logger->warning('Customer cache refresh failed', [
            'operation' => 'cache.refresh.error',
            'family' => $command->family(),
            'dedupe_key' => $command->dedupeKey(),
            'error' => $e->getMessage(),
        ]);
    }
}
