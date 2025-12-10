# Complete Cache Implementation Example

Full working example of a repository with production-grade caching, including cache policies, invalidation, SWR, and observability.

## Complete CustomerRepository with Caching

```php
<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Persistence;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Customer Repository with Production-Grade Caching
 *
 * Cache Policies:
 * - findById: TTL 600s, SWR, tag-based invalidation
 * - findActiveCustomers: TTL 300s, invalidate on create/update/delete
 * - findByEmail: TTL 300s, invalidate on email change
 */
final class CustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly TagAwareCacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Cache Policy: findById
     *
     * Key Pattern: customer.{id}
     * TTL: 600s (10 minutes)
     * Consistency: Stale-While-Revalidate
     * Invalidation: On customer update/delete
     * Tags: [customer, customer.{id}]
     */
    public function findById(string $id): ?Customer
    {
        $cacheKey = $this->buildCacheKey('customer', $id);
        $startTime = microtime(true);

        try {
            $customer = $this->cache->get(
                $cacheKey,
                function (ItemInterface $item) use ($id, $cacheKey) {
                    // TTL: 10 minutes
                    $item->expiresAfter(600);

                    // Tags for invalidation
                    $item->tag(['customer', "customer.{$id}"]);

                    $this->logger->info('Cache miss - loading customer from database', [
                        'cache_key' => $cacheKey,
                        'customer_id' => $id,
                        'operation' => 'cache.miss',
                    ]);

                    // Load from database
                    return $this->dm->find(Customer::class, $id);
                },
                beta: 1.0 // Enable SWR (stale-while-revalidate)
            );

            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->debug('Customer query completed', [
                'cache_key' => $cacheKey,
                'customer_id' => $id,
                'duration_ms' => $duration,
                'found' => $customer !== null,
                'operation' => 'cache.hit',
            ]);

            return $customer;

        } catch (\Throwable $e) {
            $this->logger->error('Cache error - falling back to database', [
                'cache_key' => $cacheKey,
                'customer_id' => $id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to database on cache failure
            return $this->dm->find(Customer::class, $id);
        }
    }

    /**
     * Cache Policy: findActiveCustomers
     *
     * Key Pattern: customer.list.active.page.{page}
     * TTL: 300s (5 minutes)
     * Consistency: Eventual
     * Invalidation: On customer create/update/delete
     * Tags: [customer, customer.list, customer.list.active]
     */
    public function findActiveCustomers(int $page = 1, int $limit = 20): array
    {
        $cacheKey = $this->buildCacheKey('customer', 'list', 'active', 'page', (string)$page);

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($page, $limit, $cacheKey) {
                $item->expiresAfter(300); // 5 minutes
                $item->tag(['customer', 'customer.list', 'customer.list.active']);

                $this->logger->info('Cache miss - loading active customers', [
                    'cache_key' => $cacheKey,
                    'page' => $page,
                    'limit' => $limit,
                ]);

                return $this->queryActiveCustomers($page, $limit);
            }
        );
    }

    /**
     * Cache Policy: findByEmail
     *
     * Key Pattern: customer.email.{hash}
     * TTL: 300s (5 minutes)
     * Consistency: Eventual
     * Invalidation: On customer update (if email changed)
     * Tags: [customer, customer.email]
     */
    public function findByEmail(string $email): ?Customer
    {
        $emailHash = hash('sha256', strtolower($email));
        $cacheKey = $this->buildCacheKey('customer', 'email', $emailHash);

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($email, $emailHash) {
                $item->expiresAfter(300);
                $item->tag(['customer', 'customer.email', "customer.email.{$emailHash}"]);

                return $this->dm->getRepository(Customer::class)->findOneBy([
                    'email' => $email,
                ]);
            }
        );
    }

    /**
     * Save with Explicit Cache Invalidation
     */
    public function save(Customer $customer): void
    {
        $customerId = $customer->id();
        $startTime = microtime(true);

        $this->logger->info('Saving customer', [
            'customer_id' => $customerId,
        ]);

        try {
            // Persist to database
            $this->dm->persist($customer);
            $this->dm->flush();

            // Explicit cache invalidation
            $this->invalidateCustomerCache($customerId, $customer->email());

            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->info('Customer saved and cache invalidated', [
                'customer_id' => $customerId,
                'duration_ms' => $duration,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to save customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete with Explicit Cache Invalidation
     */
    public function delete(Customer $customer): void
    {
        $customerId = $customer->id();
        $customerEmail = $customer->email();

        $this->logger->info('Deleting customer', [
            'customer_id' => $customerId,
        ]);

        try {
            // Remove from database
            $this->dm->remove($customer);
            $this->dm->flush();

            // Explicit cache invalidation
            $this->invalidateCustomerCache($customerId, $customerEmail);

            $this->logger->info('Customer deleted and cache invalidated', [
                'customer_id' => $customerId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Invalidate all caches related to a customer
     */
    private function invalidateCustomerCache(string $customerId, string $email): void
    {
        $emailHash = hash('sha256', strtolower($email));

        // Invalidate specific customer cache
        $tagsToInvalidate = [
            "customer.{$customerId}",       // Specific customer
            'customer.list',                 // All customer lists
            "customer.email.{$emailHash}",  // Email-based lookup
        ];

        $this->cache->invalidateTags($tagsToInvalidate);

        $this->logger->debug('Cache invalidated', [
            'customer_id' => $customerId,
            'tags' => $tagsToInvalidate,
        ]);
    }

    /**
     * Build cache key from parts
     */
    private function buildCacheKey(string $prefix, string ...$parts): string
    {
        return $prefix . '.' . implode('.', $parts);
    }

    /**
     * Query active customers from database
     */
    private function queryActiveCustomers(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        return $this->dm->getRepository(Customer::class)->findBy(
            ['status' => 'active'],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }
}
```

---

## Command Handler with Cache Invalidation

```php
<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\UpdateCustomerCommand;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Application\CommandHandler\CommandHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class UpdateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(UpdateCustomerCommand $command): void
    {
        $correlationId = $this->generateCorrelationId();
        $startTime = microtime(true);

        $this->logger->info('Processing UpdateCustomerCommand', [
            'correlation_id' => $correlationId,
            'customer_id' => $command->id,
            'changed_fields' => $command->changedFields,
        ]);

        try {
            // Load customer (from cache if available)
            $customer = $this->repository->findById($command->id);

            if ($customer === null) {
                throw new \DomainException("Customer not found: {$command->id}");
            }

            // Update customer
            if (isset($command->name)) {
                $customer->updateName($command->name);
            }

            if (isset($command->email)) {
                $customer->updateEmail($command->email);
            }

            // Save with automatic cache invalidation
            $this->repository->save($customer);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->info('Customer updated successfully', [
                'correlation_id' => $correlationId,
                'customer_id' => $command->id,
                'duration_ms' => $duration,
            ]);

        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->error('Failed to update customer', [
                'correlation_id' => $correlationId,
                'customer_id' => $command->id,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function generateCorrelationId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
```

---

## Cache Warming Service

```php
<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Cache;

use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Cache Warming Service
 *
 * Pre-populates cache with frequently accessed data
 * Run on application startup or scheduled intervals
 */
final readonly class CustomerCacheWarmer
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * Warm cache with top customers
     */
    public function warmTopCustomers(int $limit = 100): void
    {
        $startTime = microtime(true);

        $this->logger->info('Starting customer cache warmup', [
            'limit' => $limit,
        ]);

        try {
            $topCustomerIds = $this->getTopCustomerIds($limit);

            $warmedCount = 0;
            foreach ($topCustomerIds as $customerId) {
                // Load customer - will populate cache
                $customer = $this->repository->findById($customerId);

                if ($customer !== null) {
                    $warmedCount++;
                }
            }

            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->info('Customer cache warmup completed', [
                'warmed_count' => $warmedCount,
                'requested_count' => $limit,
                'duration_ms' => $duration,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Cache warmup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Warm cache with active customer list
     */
    public function warmActiveCustomersList(int $pages = 5): void
    {
        $this->logger->info('Warming active customers list cache', [
            'pages' => $pages,
        ]);

        for ($page = 1; $page <= $pages; $page++) {
            // Load page - will populate cache
            $this->repository->findActiveCustomers($page);
        }

        $this->logger->info('Active customers list cache warmed', [
            'pages_warmed' => $pages,
        ]);
    }

    /**
     * Get IDs of most frequently accessed customers
     */
    private function getTopCustomerIds(int $limit): array
    {
        // In production, this could be based on:
        // - Analytics data
        // - Recent access logs
        // - Business importance
        // For now, return active customers
        return ['customer-1', 'customer-2', 'customer-3']; // Placeholder
    }
}
```

---

## Console Command for Cache Management

```php
<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Customer\Infrastructure\Cache\CustomerCacheWarmer;

#[AsCommand(
    name: 'cache:customer',
    description: 'Manage customer cache (warm, invalidate, clear)'
)]
final class CustomerCacheCommand extends Command
{
    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        private readonly CustomerCacheWarmer $cacheWarmer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action: warm, invalidate, clear')
            ->addOption('customer-id', 'c', InputOption::VALUE_OPTIONAL, 'Customer ID for invalidation')
            ->addOption('tags', 't', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Tags to invalidate')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit for cache warming', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');

        return match($action) {
            'warm' => $this->warmCache($input, $output),
            'invalidate' => $this->invalidateCache($input, $output),
            'clear' => $this->clearCache($output),
            default => $this->handleInvalidAction($output, $action),
        };
    }

    private function warmCache(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int)$input->getOption('limit');

        $output->writeln("<info>Warming customer cache (limit: {$limit})...</info>");

        $this->cacheWarmer->warmTopCustomers($limit);
        $this->cacheWarmer->warmActiveCustomersList();

        $output->writeln('<info>Cache warming completed!</info>');

        return Command::SUCCESS;
    }

    private function invalidateCache(InputInterface $input, OutputInterface $output): int
    {
        $customerId = $input->getOption('customer-id');
        $tags = $input->getOption('tags');

        if ($customerId) {
            $this->cache->invalidateTags(["customer.{$customerId}"]);
            $output->writeln("<info>Invalidated cache for customer: {$customerId}</info>");
        } elseif (!empty($tags)) {
            $this->cache->invalidateTags($tags);
            $output->writeln('<info>Invalidated cache tags: ' . implode(', ', $tags) . '</info>');
        } else {
            $output->writeln('<error>Please provide --customer-id or --tags</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function clearCache(OutputInterface $output): int
    {
        $this->cache->clear();
        $output->writeln('<info>All customer cache cleared!</info>');

        return Command::SUCCESS;
    }

    private function handleInvalidAction(OutputInterface $output, string $action): int
    {
        $output->writeln("<error>Invalid action: {$action}</error>");
        $output->writeln('<info>Valid actions: warm, invalidate, clear</info>');

        return Command::FAILURE;
    }
}
```

**Usage**:

```bash
# Warm cache
php bin/console cache:customer warm --limit=200

# Invalidate specific customer
php bin/console cache:customer invalidate --customer-id=abc123

# Invalidate by tags
php bin/console cache:customer invalidate --tags=customer.list --tags=customer.email

# Clear all customer cache
php bin/console cache:customer clear
```

---

## Summary

This complete example demonstrates:

✅ **Cache policies declared** for each query
✅ **Read-through caching** with SWR
✅ **Explicit invalidation** on writes
✅ **Cache tags** for flexible invalidation
✅ **Observability** (structured logs)
✅ **Cache warming** for cold starts
✅ **Console commands** for cache management
✅ **Error handling** and fallbacks
✅ **Production-ready** patterns

**File locations** (following hexagonal architecture):
- Repository: `src/Customer/Infrastructure/Persistence/CustomerRepository.php`
- Command Handler: `src/Customer/Application/CommandHandler/UpdateCustomerCommandHandler.php`
- Cache Warmer: `src/Customer/Infrastructure/Cache/CustomerCacheWarmer.php`
- Console Command: `src/Customer/Infrastructure/Console/CustomerCacheCommand.php`
