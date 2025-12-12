<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class MongoCustomerRepositoryTest extends UnitTestCase
{
    private ManagerRegistry $registry;

    private DocumentManager $documentManager;

    private TagAwareCacheInterface $cache;

    private LoggerInterface $logger;

    private MongoCustomerRepository|MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this
            ->createMock(ManagerRegistry::class);
        $this->documentManager = $this
            ->createMock(DocumentManager::class);
        $this->cache = $this
            ->createMock(TagAwareCacheInterface::class);
        $this->logger = $this
            ->createMock(LoggerInterface::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Customer::class)
            ->willReturn($this->documentManager);

        $this->repository = $this
            ->getMockBuilder(MongoCustomerRepository::class)
            ->setConstructorArgs([$this->registry, $this->cache, $this->logger])
            ->onlyMethods(['findOneBy'])
            ->getMock();
    }

    public function testFindUsesCacheAndLoadsFromDatabaseOnMiss(): void
    {
        $id = (string) $this->faker->ulid();
        $customer = $this->createMock(Customer::class);
        $item = $this->createMock(ItemInterface::class);

        $item->expects($this->once())
            ->method('expiresAfter')
            ->with(600);

        $item->expects($this->once())
            ->method('tag')
            ->with(['customer', "customer.{$id}"]);

        $this->documentManager->expects($this->once())
            ->method('find')
            ->with(Customer::class, $id, 0, null)
            ->willReturn($customer);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('customer.' . $id, $this->isType('callable'), 1.0)
            ->willReturnCallback(static function (...$args) use ($item) {
                $callback = $args[1];

                return $callback($item);
            });

        self::assertSame($customer, $this->repository->find($id));
    }

    public function testFindFallsBackToDatabaseWhenCacheFails(): void
    {
        $id = (string) $this->faker->ulid();
        $customer = $this->createMock(Customer::class);

        $this->cache->expects($this->once())
            ->method('get')
            ->willThrowException(new \RuntimeException('cache down'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->arrayHasKey('cache_key')
            );

        $this->documentManager->expects($this->once())
            ->method('find')
            ->with(Customer::class, $id, 0, null)
            ->willReturn($customer);

        self::assertSame($customer, $this->repository->find($id));
    }

    public function testSave(): void
    {
        $customer = $this->createMock(Customer::class);

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($customer);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($customer);
    }

    public function testFindByEmail(): void
    {
        $email = $this->faker->email();
        $customer = $this->createMock(Customer::class);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($customer);

        $result = $this->repository->findByEmail($email);

        $this->assertSame($customer, $result);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $email = $this->faker->email();

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $result = $this->repository->findByEmail($email);

        $this->assertNull($result);
    }

    public function testFindByEmailLoadsFromRepositoryOnMissAndSetsCacheTags(): void
    {
        $email = 'Customer@Example.com';
        $emailHash = hash('sha256', strtolower($email));
        $expectedCacheKey = 'customer.email.' . $emailHash;

        $customer = $this->createMock(Customer::class);
        $item = $this->createMock(ItemInterface::class);

        $item->expects($this->once())
            ->method('expiresAfter')
            ->with(300);

        $item->expects($this->once())
            ->method('tag')
            ->with(['customer', 'customer.email', "customer.email.{$emailHash}"]);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($customer);

        $this->cache->expects($this->once())
            ->method('get')
            ->with($expectedCacheKey, $this->isType('callable'))
            ->willReturnCallback(static function (...$args) use ($item) {
                $callback = $args[1];

                return $callback($item);
            });

        self::assertSame($customer, $this->repository->findByEmail($email));
    }

    public function testFindByEmailFallsBackToRepositoryWhenCacheFails(): void
    {
        $email = 'customer@example.com';
        $customer = $this->createMock(Customer::class);

        $this->cache->expects($this->once())
            ->method('get')
            ->willThrowException(new \RuntimeException('cache down'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->arrayHasKey('cache_key')
            );

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($customer);

        self::assertSame($customer, $this->repository->findByEmail($email));
    }

    public function testDelete(): void
    {
        $customer = $this->createMock(Customer::class);

        $this->documentManager->expects($this->once())
            ->method('contains')
            ->with($customer)
            ->willReturn(true);

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($customer);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($customer);
    }

    public function testDeleteMergesCustomerWhenDetached(): void
    {
        $customer = $this->createMock(Customer::class);
        $managedCustomer = $this->createMock(Customer::class);

        $this->documentManager->expects($this->once())
            ->method('contains')
            ->with($customer)
            ->willReturn(false);

        $this->documentManager->expects($this->once())
            ->method('merge')
            ->with($customer)
            ->willReturn($managedCustomer);

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($managedCustomer);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($customer);
    }

    public function testDeleteDelegatesToParentWhenEntityIsNotCustomer(): void
    {
        $entity = new \stdClass();

        $this->documentManager->expects($this->never())
            ->method('contains');

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($entity);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($entity);
    }
}
