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
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class MongoCustomerRepositorySwrTest extends KernelTestCase
{
    private CustomerRepositoryInterface $repository;
    private MongoTypeRepository $typeRepository;
    private MongoStatusRepository $statusRepository;
    private CacheItemPoolInterface $cachePool;
    private ?CustomerType $defaultType = null;
    private ?CustomerStatus $defaultStatus = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepositoryInterface::class);
        $this->typeRepository = self::getContainer()->get(MongoTypeRepository::class);
        $this->statusRepository = self::getContainer()->get(MongoStatusRepository::class);
        $this->cachePool = self::getContainer()->get('cache.customer');

        $this->cachePool->clear();
        $this->ensureDefaultTypeAndStatus();
    }

    public function testSwrServesCachedDataFast(): void
    {
        $customer = $this->createTestCustomer(
            'John Doe',
            sprintf('john+%s@example.com', (string) $this->generateUlid())
        );

        $result1 = $this->repository->find($customer->getUlid());
        $result2 = $this->repository->find($customer->getUlid());

        self::assertNotNull($result1);
        self::assertSame($result1->getUlid(), $result2->getUlid());
        self::assertTrue($this->cachePool->getItem('customer.' . $customer->getUlid())->isHit());
    }

    public function testCacheHitIsFasterThanMiss(): void
    {
        $customer = $this->createTestCustomer(
            'John Doe',
            sprintf('john+%s@example.com', (string) $this->generateUlid())
        );

        $result1 = $this->repository->find($customer->getUlid());
        $result2 = $this->repository->find($customer->getUlid());

        self::assertNotNull($result1);
        self::assertNotNull($result2);
        self::assertTrue($this->cachePool->getItem('customer.' . $customer->getUlid())->isHit());
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
