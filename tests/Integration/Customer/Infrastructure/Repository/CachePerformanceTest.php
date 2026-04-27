<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Repository\MongoStatusRepository;
use App\Core\Customer\Infrastructure\Repository\MongoTypeRepository;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheRefreshCommandHandler;
use App\Shared\Domain\ValueObject\Ulid;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * Performance tests to verify caching actually improves response times.
 *
 * These tests measure actual latency differences between cache hits and misses
 * to ensure the caching implementation provides real performance benefits.
 */
final class CachePerformanceTest extends KernelTestCase
{
    private const PERFORMANCE_ITERATIONS = 10;
    private const MAX_CACHE_HIT_LATENCY_MS = 10;
    private const MAX_GUARDED_CACHE_HIT_LATENCY_MS = 100;
    private const MAX_ASYNC_REFRESH_LATENCY_MS = 5000;
    private const MIN_SPEEDUP_FACTOR = 2.0;

    private CustomerRepositoryInterface $repository;
    private MongoTypeRepository $typeRepository;
    private MongoStatusRepository $statusRepository;
    private CacheItemPoolInterface $cachePool;
    private InMemoryTransport $cacheRefreshTransport;
    private CacheRefreshCommandHandler $cacheRefreshHandler;
    private ?CustomerType $defaultType = null;
    private ?CustomerStatus $defaultStatus = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepositoryInterface::class);
        $this->typeRepository = self::getContainer()->get(MongoTypeRepository::class);
        $this->statusRepository = self::getContainer()->get(MongoStatusRepository::class);
        $this->cachePool = self::getContainer()->get('cache.customer');
        $this->cacheRefreshTransport = self::getContainer()->get(
            'messenger.transport.cache-refresh'
        );
        $this->cacheRefreshHandler = self::getContainer()->get(CacheRefreshCommandHandler::class);

        $this->cachePool->clear();
        $this->ensureDefaultTypeAndStatus();
        $this->cacheRefreshTransport->reset();
    }

    public function testCacheHitIsSignificantlyFasterThanMiss(): void
    {
        $customer = $this->createTestCustomer(
            'Performance Test',
            sprintf('perf+%s@example.com', (string) $this->generateUlid())
        );

        $this->cachePool->clear();

        $cacheMissStart = hrtime(true);
        $this->repository->find($customer->getUlid());
        $cacheMissEnd = hrtime(true);
        $cacheMissLatencyNs = $cacheMissEnd - $cacheMissStart;

        $cacheHitStart = hrtime(true);
        $this->repository->find($customer->getUlid());
        $cacheHitEnd = hrtime(true);
        $cacheHitLatencyNs = $cacheHitEnd - $cacheHitStart;

        $cacheMissLatencyMs = $cacheMissLatencyNs / 1_000_000;
        $cacheHitLatencyMs = $cacheHitLatencyNs / 1_000_000;

        self::assertLessThan(
            $cacheMissLatencyMs,
            $cacheHitLatencyMs,
            sprintf(
                'Cache hit (%.2fms) should be faster than cache miss (%.2fms)',
                $cacheHitLatencyMs,
                $cacheMissLatencyMs
            )
        );

        if ($cacheMissLatencyMs > 0) {
            $speedupFactor = $cacheMissLatencyMs / max($cacheHitLatencyMs, 0.001);
            self::assertGreaterThanOrEqual(
                self::MIN_SPEEDUP_FACTOR,
                $speedupFactor,
                sprintf(
                    'Cache should provide at least %.1fx speedup, got %.1fx (miss: %.2fms, hit: %.2fms)',
                    self::MIN_SPEEDUP_FACTOR,
                    $speedupFactor,
                    $cacheMissLatencyMs,
                    $cacheHitLatencyMs
                )
            );
        }
    }

    public function testAverageCacheHitLatencyIsAcceptable(): void
    {
        $customer = $this->createTestCustomer(
            'Latency Test',
            sprintf('latency+%s@example.com', (string) $this->generateUlid())
        );

        $this->repository->find($customer->getUlid());

        $totalLatencyNs = 0;
        for ($i = 0; $i < self::PERFORMANCE_ITERATIONS; $i++) {
            $start = hrtime(true);
            $this->repository->find($customer->getUlid());
            $end = hrtime(true);
            $totalLatencyNs += $end - $start;
        }

        $averageLatencyMs = $totalLatencyNs / self::PERFORMANCE_ITERATIONS / 1_000_000;

        self::assertLessThanOrEqual(
            self::MAX_CACHE_HIT_LATENCY_MS,
            $averageLatencyMs,
            sprintf(
                'Average cache hit latency (%.2fms) exceeds maximum allowed (%dms)',
                $averageLatencyMs,
                self::MAX_CACHE_HIT_LATENCY_MS
            )
        );
    }

    public function testCacheHitRatioAfterWarmup(): void
    {
        $customers = [];
        for ($i = 0; $i < 5; $i++) {
            $customers[] = $this->createTestCustomer(
                sprintf('Customer %d', $i),
                sprintf('customer%d+%s@example.com', $i, (string) $this->generateUlid())
            );
        }

        foreach ($customers as $customer) {
            $this->repository->find($customer->getUlid());
        }

        $hits = 0;
        $total = 0;
        foreach ($customers as $customer) {
            for ($j = 0; $j < 3; $j++) {
                $cacheKey = 'customer.' . $customer->getUlid();
                $isHit = $this->cachePool->getItem($cacheKey)->isHit();
                if ($isHit) {
                    $hits++;
                }
                $total++;
                $this->repository->find($customer->getUlid());
            }
        }

        $hitRatio = $hits / $total;

        self::assertGreaterThanOrEqual(
            0.9,
            $hitRatio,
            sprintf(
                'Cache hit ratio (%.1f%%) should be at least 90%% after warmup',
                $hitRatio * 100
            )
        );
    }

    public function testEmailLookupCachePerformance(): void
    {
        $email = sprintf('email-perf+%s@example.com', (string) $this->generateUlid());
        $this->createTestCustomer('Email Perf Test', $email);

        $this->cachePool->clear();

        $cacheMissStart = hrtime(true);
        $this->repository->findByEmail($email);
        $cacheMissEnd = hrtime(true);
        $cacheMissLatencyNs = $cacheMissEnd - $cacheMissStart;

        $cacheHitStart = hrtime(true);
        $this->repository->findByEmail($email);
        $cacheHitEnd = hrtime(true);
        $cacheHitLatencyNs = $cacheHitEnd - $cacheHitStart;

        self::assertLessThan(
            $cacheMissLatencyNs,
            $cacheHitLatencyNs,
            'Email lookup cache hit should be faster than cache miss'
        );

        $emailHash = hash('sha256', strtolower($email));
        self::assertTrue(
            $this->cachePool->getItem('customer.email.' . $emailHash)->isHit(),
            'Email lookup should be cached after first query'
        );
    }

    public function testAsyncRefreshWarmsCacheBeforeNextRead(): void
    {
        $customer = $this->createTestCustomer(
            'Async Refresh Perf',
            sprintf('async-refresh+%s@example.com', (string) $this->generateUlid())
        );
        $customerId = (string) $customer->getUlid();
        $cacheKey = 'customer.' . $customerId;

        $this->cachePool->clear();
        $this->repository->find($customerId);
        self::assertTrue($this->cachePool->getItem($cacheKey)->isHit());
        $this->cacheRefreshTransport->reset();

        $customer->setInitials('Async Refresh Perf Updated');
        $this->repository->save($customer);
        self::assertFalse($this->cachePool->getItem($cacheKey)->isHit());
        self::assertNotEmpty($this->cacheRefreshTransport->getSent());

        $this->handleCacheRefreshMessages();

        self::assertTrue($this->cachePool->getItem($cacheKey)->isHit());

        $result = $this->repository->find($customerId);

        self::assertNotNull($result);
        self::assertSame('Async Refresh Perf Updated', $result->getInitials());
    }

    /**
     * @group performance
     */
    public function testAsyncRefreshWarmsCacheBeforeNextReadWithinGuardedLatencyBudget(): void
    {
        if (getenv('CI_PERFORMANCE_GUARDED') !== '1') {
            self::markTestSkipped('Guarded latency budget runs only when CI_PERFORMANCE_GUARDED=1.');
        }

        $customer = $this->createTestCustomer(
            'Async Refresh Perf Guarded',
            sprintf('async-refresh-guarded+%s@example.com', (string) $this->generateUlid())
        );
        $customerId = (string) $customer->getUlid();
        $cacheKey = 'customer.' . $customerId;

        $this->cachePool->clear();
        $this->repository->find($customerId);
        self::assertTrue($this->cachePool->getItem($cacheKey)->isHit());
        $this->cacheRefreshTransport->reset();

        $customer->setInitials('Async Refresh Perf Guarded Updated');
        $this->repository->save($customer);
        self::assertFalse($this->cachePool->getItem($cacheKey)->isHit());
        self::assertNotEmpty($this->cacheRefreshTransport->getSent());

        $refreshStart = hrtime(true);
        $this->handleCacheRefreshMessages();
        $refreshLatencyMs = (hrtime(true) - $refreshStart) / 1_000_000;

        self::assertTrue($this->cachePool->getItem($cacheKey)->isHit());

        $hitStart = hrtime(true);
        $result = $this->repository->find($customerId);
        $hitLatencyMs = (hrtime(true) - $hitStart) / 1_000_000;

        self::assertNotNull($result);
        self::assertSame('Async Refresh Perf Guarded Updated', $result->getInitials());
        self::assertLessThanOrEqual(
            self::MAX_GUARDED_CACHE_HIT_LATENCY_MS,
            $hitLatencyMs,
            sprintf(
                'Read after async refresh should stay under %dms, got %.2fms',
                self::MAX_GUARDED_CACHE_HIT_LATENCY_MS,
                $hitLatencyMs
            )
        );
        self::assertLessThan(
            self::MAX_ASYNC_REFRESH_LATENCY_MS,
            $refreshLatencyMs,
            sprintf(
                'Async refresh should complete within %dms, got %.2fms',
                self::MAX_ASYNC_REFRESH_LATENCY_MS,
                $refreshLatencyMs
            )
        );
    }

    public function testCacheRecoveryAfterInvalidation(): void
    {
        $customer = $this->createTestCustomer(
            'Invalidation Perf',
            sprintf('invalidation+%s@example.com', (string) $this->generateUlid())
        );

        $this->repository->find($customer->getUlid());
        self::assertTrue(
            $this->cachePool->getItem('customer.' . $customer->getUlid())->isHit(),
            'Cache should be populated after first query'
        );

        $this->cachePool->clear();
        self::assertFalse(
            $this->cachePool->getItem('customer.' . $customer->getUlid())->isHit(),
            'Cache should be empty after clear'
        );

        $this->repository->find($customer->getUlid());
        self::assertTrue(
            $this->cachePool->getItem('customer.' . $customer->getUlid())->isHit(),
            'Cache should be repopulated after query following clear'
        );

        $cacheHitStart = hrtime(true);
        $this->repository->find($customer->getUlid());
        $cacheHitEnd = hrtime(true);
        $cacheHitLatencyMs = ($cacheHitEnd - $cacheHitStart) / 1_000_000;

        self::assertLessThanOrEqual(
            self::MAX_CACHE_HIT_LATENCY_MS,
            $cacheHitLatencyMs,
            sprintf(
                'Cache hit after re-warmup (%.2fms) should still be fast (<%dms)',
                $cacheHitLatencyMs,
                self::MAX_CACHE_HIT_LATENCY_MS
            )
        );
    }

    private function ensureDefaultTypeAndStatus(): void
    {
        if ($this->defaultType === null) {
            $existing = $this->typeRepository->findOneByCriteria(['value' => 'individual']);
            $this->defaultType = $existing instanceof CustomerType
                ? $existing
                : new CustomerType('individual', $this->generateUlid());
            if (! $existing) {
                $this->typeRepository->save($this->defaultType);
            }
        }

        if ($this->defaultStatus === null) {
            $existing = $this->statusRepository->findOneByCriteria(['value' => 'active']);
            $this->defaultStatus = $existing instanceof CustomerStatus
                ? $existing
                : new CustomerStatus('active', $this->generateUlid());
            if (! $existing) {
                $this->statusRepository->save($this->defaultStatus);
            }
        }
    }

    private function createTestCustomer(string $initials, string $email): Customer
    {
        $customer = new Customer(
            initials: $initials,
            email: $email,
            phone: '+1234567890',
            leadSource: 'test',
            type: $this->defaultType,
            status: $this->defaultStatus,
            confirmed: true,
            ulid: $this->generateUlid()
        );

        $this->repository->save($customer);

        return $customer;
    }

    private function handleCacheRefreshMessages(): void
    {
        foreach ($this->cacheRefreshTransport->getSent() as $envelope) {
            $this->handleCacheRefreshEnvelope($envelope);
        }
    }

    private function handleCacheRefreshEnvelope(Envelope $envelope): void
    {
        $message = $envelope->getMessage();

        self::assertInstanceOf(CacheRefreshCommand::class, $message);
        ($this->cacheRefreshHandler)($message);
    }

    private function generateUlid(): Ulid
    {
        return new Ulid((string) new SymfonyUlid());
    }
}
