<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Repository\MongoStatusRepository;
use App\Core\Customer\Infrastructure\Repository\MongoTypeRepository;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Ulid;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class MongoCustomerRepositoryInvalidationTest extends KernelTestCase
{
    private CustomerRepositoryInterface $repository;
    private EventBusInterface $eventBus;
    private MongoTypeRepository $typeRepository;
    private MongoStatusRepository $statusRepository;
    private CacheItemPoolInterface $cachePool;
    private ?CustomerType $defaultType = null;
    private ?CustomerStatus $defaultStatus = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepositoryInterface::class);
        $this->eventBus = self::getContainer()->get(EventBusInterface::class);
        $this->typeRepository = self::getContainer()->get(MongoTypeRepository::class);
        $this->statusRepository = self::getContainer()->get(MongoStatusRepository::class);
        $this->cachePool = self::getContainer()->get('cache.customer');

        $this->cachePool->clear();
        $this->ensureDefaultTypeAndStatus();
    }

    public function testCacheInvalidatedAfterUpdate(): void
    {
        $customer = $this->createTestCustomer(
            'John Doe',
            sprintf('john+%s@example.com', (string) $this->generateUlid())
        );
        $customerId = $customer->getUlid();

        $result1 = $this->repository->find($customerId);
        self::assertNotNull($result1);
        self::assertSame('John Doe', $result1->getInitials());

        $result2 = $this->repository->find($customerId);
        self::assertSame('John Doe', $result2->getInitials());

        // Update customer and publish event for cache invalidation
        $customer->setInitials('Jane Doe');
        $this->repository->save($customer);

        // Event-driven cache invalidation
        $this->eventBus->publish(
            new CustomerUpdatedEvent(
                customerId: (string) $customerId,
                currentEmail: $customer->getEmail(),
                previousEmail: null // Email didn't change
            )
        );

        $result3 = $this->repository->find($customerId);
        self::assertSame('Jane Doe', $result3->getInitials());

        self::assertNotSame($result2->getInitials(), $result3->getInitials());
    }

    public function testCacheInvalidatedAfterDelete(): void
    {
        $customer = $this->createTestCustomer(
            'John Doe',
            sprintf('john+%s@example.com', (string) $this->generateUlid())
        );
        $customerId = $customer->getUlid();
        $customerEmail = $customer->getEmail();

        $result1 = $this->repository->find($customerId);
        self::assertNotNull($result1);

        $this->repository->delete($customer);

        // Event-driven cache invalidation
        $this->eventBus->publish(
            new CustomerDeletedEvent(
                customerId: (string) $customerId,
                customerEmail: $customerEmail
            )
        );

        $result2 = $this->repository->find($customerId);
        self::assertNull($result2);
    }

    public function testEmailCacheInvalidatedAfterEmailChange(): void
    {
        $oldEmail = sprintf('john+%s@example.com', (string) $this->generateUlid());
        $newEmail = sprintf('jane+%s@example.com', (string) $this->generateUlid());
        $customer = $this->createTestCustomer('John Doe', $oldEmail);

        $result1 = $this->repository->findByEmail($oldEmail);
        self::assertNotNull($result1);
        self::assertTrue($this->cachePool->getItem('customer.email.' . hash('sha256', strtolower($oldEmail)))->isHit());

        // Change email and save
        $customer->setEmail($newEmail);
        $this->repository->save($customer);

        // Event-driven cache invalidation with email change
        // This tests the edge case where both old and new email caches are invalidated
        $this->eventBus->publish(
            new CustomerUpdatedEvent(
                customerId: (string) $customer->getUlid(),
                currentEmail: $newEmail,
                previousEmail: $oldEmail // Email changed, so previousEmail is set
            )
        );

        // Verify old email cache is invalidated
        self::assertFalse($this->cachePool->getItem('customer.email.' . hash('sha256', strtolower($oldEmail)))->isHit());
        $result2 = $this->repository->findByEmail($oldEmail);
        self::assertNull($result2);

        // Verify new email works
        $result3 = $this->repository->findByEmail($newEmail);
        self::assertNotNull($result3);
        self::assertSame($customer->getUlid(), $result3->getUlid());
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
