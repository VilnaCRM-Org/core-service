<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\CommandHandler\UpdateCustomerCommandHandler;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Core\Customer\Infrastructure\Repository\MongoStatusRepository;
use App\Core\Customer\Infrastructure\Repository\MongoTypeRepository;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheRefreshCommandHandler;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Ulid;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Uid\Ulid as SymfonyUlid;
use Symfony\Contracts\Cache\ItemInterface;

final class MongoCustomerRepositoryInvalidationTest extends KernelTestCase
{
    private CustomerRepositoryInterface $repository;
    private MongoTypeRepository $typeRepository;
    private MongoStatusRepository $statusRepository;
    private CacheItemPoolInterface $cachePool;
    private InMemoryTransport $cacheRefreshTransport;
    private CacheRefreshCommandHandler $cacheRefreshHandler;
    private EventBusInterface $eventBus;
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
        $this->eventBus = self::getContainer()->get(EventBusInterface::class);

        $this->cachePool->clear();
        $this->ensureDefaultTypeAndStatus();
        $this->cacheRefreshTransport->reset();
    }

    public function testCacheInvalidatedAfterUpdate(): void
    {
        $customer = $this->createUniqueCustomer('John Doe', 'john');
        $customerId = $customer->getUlid();

        $cachedCustomer = $this->primeCustomerCache((string) $customerId, 'John Doe');
        $this->cacheRefreshTransport->reset();

        $customer->setInitials('Jane Doe');
        $this->repository->save($customer);

        $this->assertCustomerCacheMiss((string) $customerId);
        self::assertCount(2, $this->cacheRefreshTransport->getSent());
        $immediateResult = $this->repository->find($customerId);
        self::assertNotNull($immediateResult);
        self::assertSame('Jane Doe', $immediateResult->getInitials());
        $this->assertCustomerCacheHit((string) $customerId);

        $this->handleCacheRefreshMessages();
        $this->assertCustomerCacheHit((string) $customerId);

        $result3 = $this->repository->find($customerId);
        self::assertSame('Jane Doe', $result3->getInitials());

        self::assertNotSame($cachedCustomer->getInitials(), $result3->getInitials());
    }

    public function testDomainEventAndOdmSignalsShareRefreshDedupeKeys(): void
    {
        $customer = $this->createUniqueCustomer('Original Name', 'overlap');
        $customerId = $customer->getUlid();
        $email = $customer->getEmail();
        $commandHandler = self::getContainer()->get(UpdateCustomerCommandHandler::class);

        self::assertNotNull($this->repository->find($customerId));
        self::assertNotNull($this->repository->findByEmail($email));
        $this->cacheRefreshTransport->reset();

        $commandHandler(new UpdateCustomerCommand(
            $customer,
            $this->customerUpdate('Overlap Updated', $email)
        ));

        $refreshCommands = $this->refreshCommands();
        self::assertCount(4, $refreshCommands);

        $dedupeCounts = array_count_values(array_map(
            static fn (CacheRefreshCommand $command): string => $command->dedupeKey(),
            $refreshCommands
        ));

        self::assertCount(2, $dedupeCounts);
        self::assertSame([2, 2], array_values($dedupeCounts));

        $this->handleCacheRefreshMessages();

        $result = $this->repository->find($customerId);
        self::assertNotNull($result);
        self::assertSame('Overlap Updated', $result->getInitials());
    }

    public function testDomainEventInvalidatesCacheWithoutOdmChange(): void
    {
        $previousEmail = sprintf('previous+%s@example.com', (string) $this->generateUlid());
        $customer = $this->createUniqueCustomer('Domain Event Only', 'domain-event-only');
        $customerId = $customer->getUlid();
        $currentEmail = $customer->getEmail();

        self::assertNotNull($this->repository->find($customerId));
        self::assertNotNull($this->repository->findByEmail($currentEmail));
        $this->warmTaggedCacheItem(
            $this->customerEmailCacheKey($previousEmail),
            'customer.email.' . hash('sha256', strtolower($previousEmail))
        );
        $this->assertCustomerCacheHit((string) $customerId);
        $this->assertEmailCacheHit($currentEmail);
        $this->assertEmailCacheHit($previousEmail);
        $this->cacheRefreshTransport->reset();

        $this->eventBus->publish(new CustomerUpdatedEvent(
            (string) $customerId,
            $currentEmail,
            $previousEmail,
            'domain-event-only',
            '2026-04-27T10:00:00+00:00'
        ));

        $this->assertCustomerCacheMiss((string) $customerId);
        $this->assertEmailCacheMiss($currentEmail);
        $this->assertEmailCacheMiss($previousEmail);

        $refreshCommands = $this->refreshCommands();
        self::assertCount(3, $refreshCommands);
        self::assertSame('customer_id', $refreshCommands[0]->identifierName());
        self::assertSame('email', $refreshCommands[1]->identifierName());
        self::assertSame('email', $refreshCommands[2]->identifierName());
    }

    public function testCacheInvalidatedAfterDelete(): void
    {
        $customer = $this->createUniqueCustomer('John Doe', 'john');
        $customerId = $customer->getUlid();
        $customerEmail = $customer->getEmail();

        $result1 = $this->repository->find($customerId);
        self::assertNotNull($result1);
        $this->assertCustomerCacheHit((string) $customerId);
        $this->cacheRefreshTransport->reset();

        $this->repository->delete($customer);

        $this->assertCustomerCacheMiss((string) $customerId);
        $this->assertEmailCacheMiss($customerEmail);
        self::assertCount(0, $this->cacheRefreshTransport->getSent());

        $result2 = $this->repository->find($customerId);
        self::assertNull($result2);
    }

    public function testCacheInvalidatedAfterDirectDeleteByEmail(): void
    {
        $email = sprintf('john+%s@example.com', (string) $this->generateUlid());
        $customer = $this->createTestCustomer('John Doe', $email);

        $cachedCustomer = $this->repository->findByEmail($email);
        self::assertNotNull($cachedCustomer);
        self::assertNotNull($this->repository->find($customer->getUlid()));
        $this->assertEmailCacheHit($email);
        $this->assertCustomerCacheHit((string) $customer->getUlid());

        $this->repository->deleteByEmail($email);

        $this->assertEmailCacheMiss($email);
        $this->assertCustomerCacheMiss((string) $customer->getUlid());
        self::assertNull($this->repository->find($customer->getUlid()));
        self::assertNull($this->repository->findByEmail($email));
    }

    public function testCacheInvalidatedAfterDirectDeleteById(): void
    {
        $customer = $this->createUniqueCustomer('John Doe', 'john');
        $customerId = $customer->getUlid();

        $cachedCustomer = $this->repository->find($customerId);
        self::assertNotNull($cachedCustomer);
        self::assertNotNull($this->repository->findByEmail($customer->getEmail()));
        $this->assertCustomerCacheHit((string) $customerId);
        $this->assertEmailCacheHit($customer->getEmail());

        $this->repository->deleteById($customerId);

        $this->assertCustomerCacheMiss((string) $customerId);
        $this->assertEmailCacheMiss($customer->getEmail());
        self::assertNull($this->repository->find($customerId));
        self::assertNull($this->repository->findByEmail($customer->getEmail()));
    }

    public function testRepositoryFallbackInvalidatesEmailTagWhenDeleteByEmailBypassesManagedDocument(): void
    {
        $email = sprintf('missing+%s@example.com', (string) $this->generateUlid());
        $cacheKey = $this->customerEmailCacheKey($email);
        $this->warmTaggedCacheItem(
            $cacheKey,
            'customer.email.' . hash('sha256', strtolower($email))
        );
        $this->assertEmailCacheHit($email);
        $this->cacheRefreshTransport->reset();

        $this->repository->deleteByEmail($email);

        $this->assertEmailCacheMiss($email);
        self::assertCount(0, $this->cacheRefreshTransport->getSent());
    }

    public function testRepositoryFallbackInvalidatesDetailTagWhenDeleteByIdBypassesManagedDocument(): void
    {
        $customerId = (string) $this->generateUlid();
        $cacheKey = $this->customerCacheKey($customerId);
        $this->warmTaggedCacheItem($cacheKey, 'customer.' . $customerId);
        $this->assertCustomerCacheHit($customerId);
        $this->cacheRefreshTransport->reset();

        $this->repository->deleteById($customerId);

        $this->assertCustomerCacheMiss($customerId);
        self::assertCount(0, $this->cacheRefreshTransport->getSent());
    }

    public function testEmailCacheInvalidatedAfterEmailChange(): void
    {
        $oldEmail = sprintf('john+%s@example.com', (string) $this->generateUlid());
        $newEmail = sprintf('jane+%s@example.com', (string) $this->generateUlid());
        $customer = $this->createTestCustomer('John Doe', $oldEmail);

        $result1 = $this->repository->findByEmail($oldEmail);
        self::assertNotNull($result1);
        $this->assertEmailCacheHit($oldEmail);
        $this->cacheRefreshTransport->reset();

        $customer->setEmail($newEmail);
        $this->repository->save($customer);

        $this->assertEmailCacheMiss($oldEmail);
        self::assertCount(2, $this->cacheRefreshTransport->getSent());

        $result2 = $this->repository->findByEmail($oldEmail);
        self::assertNull($result2);

        $this->handleCacheRefreshMessages();

        $result3 = $this->repository->findByEmail($newEmail);
        self::assertNotNull($result3);
        self::assertSame($customer->getUlid(), $result3->getUlid());
    }

    public function testCacheInvalidatedAfterManagedCreate(): void
    {
        $this->cachePool->clear();
        $cacheKey = 'customer.collection.warmed';
        $this->warmTaggedCacheItem($cacheKey, 'customer.collection');

        $this->assertCacheHit($cacheKey);

        $this->createTestCustomer(
            'Created Customer',
            sprintf('created+%s@example.com', (string) $this->generateUlid())
        );

        $this->assertCacheMiss($cacheKey);
        self::assertCount(2, $this->cacheRefreshTransport->getSent());
    }

    private function ensureDefaultTypeAndStatus(): void
    {
        $type = $this->typeRepository->findOneByCriteria(['value' => 'individual'])
            ?? new CustomerType('individual', $this->generateUlid());
        self::assertInstanceOf(CustomerType::class, $type);
        $this->defaultType = $type;
        $this->typeRepository->save($this->defaultType);

        $status = $this->statusRepository->findOneByCriteria(['value' => 'active'])
            ?? new CustomerStatus('active', $this->generateUlid());
        self::assertInstanceOf(CustomerStatus::class, $status);
        $this->defaultStatus = $status;
        $this->statusRepository->save($this->defaultStatus);
    }

    private function createTestCustomer(
        string $initials,
        string $email,
        ?Ulid $ulid = null
    ): Customer {
        $customer = new Customer(
            initials: $initials,
            email: $email,
            phone: '+1234567890',
            leadSource: 'test',
            type: $this->defaultType,
            status: $this->defaultStatus,
            confirmed: true,
            ulid: $ulid ?? $this->generateUlid()
        );

        $this->repository->save($customer);

        return $customer;
    }

    private function createUniqueCustomer(string $initials, string $emailPrefix): Customer
    {
        return $this->createTestCustomer(
            $initials,
            sprintf('%s+%s@example.com', $emailPrefix, (string) $this->generateUlid())
        );
    }

    private function customerUpdate(string $initials, string $email): CustomerUpdate
    {
        self::assertNotNull($this->defaultType);
        self::assertNotNull($this->defaultStatus);

        return new CustomerUpdate(
            newInitials: $initials,
            newEmail: $email,
            newPhone: '+1234567890',
            newLeadSource: 'test',
            newType: $this->defaultType,
            newStatus: $this->defaultStatus,
            newConfirmed: true
        );
    }

    private function primeCustomerCache(string $customerId, string $expectedInitials): Customer
    {
        $result1 = $this->repository->find($customerId);
        self::assertNotNull($result1);
        self::assertSame($expectedInitials, $result1->getInitials());

        $result2 = $this->repository->find($customerId);
        self::assertSame($expectedInitials, $result2->getInitials());
        $this->assertCustomerCacheHit($customerId);

        return $result2;
    }

    private function generateUlid(): Ulid
    {
        return new Ulid((string) new SymfonyUlid());
    }

    private function assertCustomerCacheHit(string $customerId): void
    {
        $this->assertCacheHit($this->customerCacheKey($customerId));
    }

    private function assertCustomerCacheMiss(string $customerId): void
    {
        $this->assertCacheMiss($this->customerCacheKey($customerId));
    }

    private function assertEmailCacheHit(string $email): void
    {
        $this->assertCacheHit($this->customerEmailCacheKey($email));
    }

    private function assertEmailCacheMiss(string $email): void
    {
        $this->assertCacheMiss($this->customerEmailCacheKey($email));
    }

    private function assertCacheHit(string $cacheKey): void
    {
        self::assertTrue($this->cachePool->getItem($cacheKey)->isHit());
    }

    private function assertCacheMiss(string $cacheKey): void
    {
        self::assertFalse($this->cachePool->getItem($cacheKey)->isHit());
    }

    private function customerCacheKey(string $customerId): string
    {
        return 'customer.' . $customerId;
    }

    private function customerEmailCacheKey(string $email): string
    {
        return 'customer.email.' . hash('sha256', strtolower($email));
    }

    private function warmTaggedCacheItem(string $key, string $tag): void
    {
        self::getContainer()->get('cache.customer')->get(
            $key,
            static function (ItemInterface $item) use ($tag): string {
                $item->tag($tag);

                return 'warmed';
            }
        );
    }

    private function handleCacheRefreshMessages(): void
    {
        foreach ($this->cacheRefreshTransport->getSent() as $envelope) {
            $message = $envelope->getMessage();

            self::assertInstanceOf(CacheRefreshCommand::class, $message);
            ($this->cacheRefreshHandler)($message);
        }
    }

    /**
     * @return list<CacheRefreshCommand>
     */
    private function refreshCommands(): array
    {
        return array_map(
            static function (Envelope $envelope): CacheRefreshCommand {
                $message = $envelope->getMessage();

                self::assertInstanceOf(CacheRefreshCommand::class, $message);

                return $message;
            },
            $this->cacheRefreshTransport->getSent()
        );
    }
}
