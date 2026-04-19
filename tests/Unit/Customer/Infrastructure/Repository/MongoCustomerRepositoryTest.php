<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoCustomerRepositoryTest extends UnitTestCase
{
    private ManagerRegistry&MockObject $registry;
    private DocumentManager&MockObject $documentManager;
    private ClassMetadata&MockObject $classMetadata;
    private MongoCustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
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

        $this->repository = new MongoCustomerRepository($this->registry);
    }

    /**
     * Note: find() is inherited from ServiceDocumentRepository and is tested in integration tests.
     * Unit testing find() would require mocking Doctrine internals which tests Doctrine's
     * implementation rather than our code.
     */
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

    public function testDeleteNonCustomerEntityThrows(): void
    {
        $entity = new \stdClass();

        $this->documentManager->expects($this->never())->method('contains');
        $this->documentManager->expects($this->never())->method('merge');
        $this->documentManager->expects($this->never())->method('remove');
        $this->documentManager->expects($this->never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected Customer instance.');

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

    public function testDeleteByEmailDeletesResolvedCustomer(): void
    {
        $email = 'test@example.com';
        $customer = $this->createMock(Customer::class);

        $repository = $this->getMockBuilder(MongoCustomerRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findByEmail', 'delete'])
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);

        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($customer);

        $repository->deleteByEmail($email);
    }

    public function testDeleteByEmailReturnsWhenCustomerDoesNotExist(): void
    {
        $email = 'test@example.com';

        $repository = $this->getMockBuilder(MongoCustomerRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findByEmail', 'delete'])
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('delete');

        $repository->deleteByEmail($email);
    }

    public function testFindFreshDelegatesToFind(): void
    {
        $id = (string) $this->faker->ulid();
        $customer = $this->createMock(Customer::class);

        $repository = $this->getMockBuilder(MongoCustomerRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['find'])
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('find')
            ->with($id, 0, null)
            ->willReturn($customer);

        $result = $repository->findFresh($id);

        self::assertSame($customer, $result);
    }

    public function testDeleteByIdDeletesResolvedCustomer(): void
    {
        $id = (string) $this->faker->ulid();
        $customer = $this->createMock(Customer::class);

        $repository = $this->getMockBuilder(MongoCustomerRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['find', 'delete'])
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($customer);

        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($customer);

        $repository->deleteById($id);
    }

    public function testDeleteByIdReturnsWhenCustomerDoesNotExist(): void
    {
        $id = (string) $this->faker->ulid();

        $repository = $this->getMockBuilder(MongoCustomerRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['find', 'delete'])
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('delete');

        $repository->deleteById($id);
    }
}
