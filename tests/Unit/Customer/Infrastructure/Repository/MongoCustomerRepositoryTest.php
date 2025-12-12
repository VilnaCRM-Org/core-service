<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoCustomerRepositoryTest extends UnitTestCase
{
    private ManagerRegistry&MockObject $registry;
    private DocumentManager&MockObject $documentManager;
    private UnitOfWork&MockObject $unitOfWork;
    private DocumentPersister&MockObject $documentPersister;
    private ClassMetadata&MockObject $classMetadata;
    private MongoCustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->documentPersister = $this->createMock(DocumentPersister::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->classMetadata->name = Customer::class;

        $this->registry
            ->method('getManagerForClass')
            ->with(Customer::class)
            ->willReturn($this->documentManager);

        $this->documentManager
            ->method('getClassMetadata')
            ->with(Customer::class)
            ->willReturn($this->classMetadata);

        $this->documentManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->repository = new MongoCustomerRepository($this->registry);
    }

    public function testFindCallsDocumentManagerFind(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customer = $this->createMock(Customer::class);

        $this->unitOfWork
            ->expects($this->once())
            ->method('tryGetById')
            ->with($customerId, $this->classMetadata)
            ->willReturn($customer);

        $result = $this->repository->find($customerId);

        self::assertSame($customer, $result);
    }

    public function testFindReturnsNullWhenNotFound(): void
    {
        $customerId = (string) $this->faker->ulid();

        $this->unitOfWork
            ->expects($this->once())
            ->method('tryGetById')
            ->with($customerId, $this->classMetadata)
            ->willReturn(null);

        $this->unitOfWork
            ->expects($this->once())
            ->method('getDocumentPersister')
            ->with(Customer::class)
            ->willReturn($this->documentPersister);

        $this->documentPersister
            ->expects($this->once())
            ->method('load')
            ->with(['_id' => $customerId], null, [], 0, null)
            ->willReturn(null);

        $result = $this->repository->find($customerId);

        self::assertNull($result);
    }

    public function testDeleteManagedCustomer(): void
    {
        $customer = $this->createMock(Customer::class);

        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($customer)
            ->willReturn(true);

        $this->documentManager
            ->expects($this->never())
            ->method('merge');

        $this->documentManager
            ->expects($this->once())
            ->method('remove')
            ->with($customer);

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->delete($customer);
    }

    public function testDeleteDetachedCustomerMergesFirst(): void
    {
        $customer = $this->createMock(Customer::class);
        $managedCustomer = $this->createMock(Customer::class);

        $this->documentManager
            ->expects($this->once())
            ->method('contains')
            ->with($customer)
            ->willReturn(false);

        $this->documentManager
            ->expects($this->once())
            ->method('merge')
            ->with($customer)
            ->willReturn($managedCustomer);

        $this->documentManager
            ->expects($this->once())
            ->method('remove')
            ->with($managedCustomer);

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->delete($customer);
    }

    public function testDeleteNonCustomerEntityCallsParentDelete(): void
    {
        $entity = new \stdClass();

        $this->documentManager
            ->expects($this->once())
            ->method('remove')
            ->with($entity);

        $this->documentManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->delete($entity);
    }

    public function testFindByEmailCallsFindOneByCriteria(): void
    {
        $email = 'test@example.com';
        $customer = $this->createMock(Customer::class);

        // Create a partial mock that only mocks findOneByCriteria
        $repository = $this->getMockBuilder(MongoCustomerRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findOneByCriteria'])
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneByCriteria')
            ->with(['email' => $email])
            ->willReturn($customer);

        $result = $repository->findByEmail($email);

        self::assertSame($customer, $result);
    }
}
