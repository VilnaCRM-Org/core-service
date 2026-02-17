<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Repository\MongoStatusRepository;
use App\Core\Customer\Infrastructure\Repository\MongoTypeRepository;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class MongoCustomerRepositoryCacheTest extends KernelTestCase
{
    private CustomerRepositoryInterface $repository;
    private MongoTypeRepository $typeRepository;
    private MongoStatusRepository $statusRepository;
    private CacheItemPoolInterface $cachePool;
    private CacheKeyBuilder $cacheKeyBuilder;
    private ?CustomerType $defaultType = null;
    private ?CustomerStatus $defaultStatus = null;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepositoryInterface::class);
        $this->typeRepository = self::getContainer()->get(MongoTypeRepository::class);
        $this->statusRepository = self::getContainer()->get(MongoStatusRepository::class);
        $this->cachePool = self::getContainer()->get('cache.customer');
        $this->cacheKeyBuilder = self::getContainer()->get(CacheKeyBuilder::class);

        $this->ensureDefaultTypeAndStatus();
    }

    public function testColdStartCacheMiss(): void
    {
        $this->cachePool->clear();

        $customer = $this->createTestCustomer(
            'John Doe',
            sprintf('john+%s@example.com', (string) $this->generateUlid())
        );

        $result = $this->repository->find($customer->getUlid());

        self::assertNotNull($result);
        self::assertSame($customer->getUlid(), $result->getUlid());
        self::assertSame('John Doe', $result->getInitials());

        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey($customer->getUlid());
        self::assertTrue($this->cachePool->getItem($cacheKey)->isHit());
    }

    public function testCacheHitAfterCacheMiss(): void
    {
        $this->cachePool->clear();

        $customer = $this->createTestCustomer(
            'Customer 1',
            sprintf('customer1+%s@example.com', (string) $this->generateUlid())
        );

        $result1 = $this->repository->find($customer->getUlid());
        self::assertNotNull($result1);

        $result2 = $this->repository->find($customer->getUlid());
        self::assertNotNull($result2);
        self::assertSame($result1->getUlid(), $result2->getUlid());

        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey($customer->getUlid());
        self::assertTrue($this->cachePool->getItem($cacheKey)->isHit());
    }

    public function testEmailCacheColdStart(): void
    {
        $this->cachePool->clear();

        $email = sprintf('john+%s@example.com', (string) $this->generateUlid());
        $customer = $this->createTestCustomer('John Doe', $email);

        $result = $this->repository->findByEmail($email);

        self::assertNotNull($result);
        self::assertSame($customer->getUlid(), $result->getUlid());

        $emailCacheKey = $this->cacheKeyBuilder->buildCustomerEmailKey($email);
        self::assertTrue($this->cachePool->getItem($emailCacheKey)->isHit());
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

    private function generateUlid(): Ulid
    {
        return new Ulid((string) new SymfonyUlid());
    }
}
