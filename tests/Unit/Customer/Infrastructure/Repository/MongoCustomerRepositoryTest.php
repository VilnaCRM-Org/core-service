<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Infrastructure\Repository\MongoCustomerRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;

final class MongoCustomerRepositoryTest extends UnitTestCase
{
    private ManagerRegistry $registry;

    private DocumentManager $documentManager;

    private MongoCustomerRepository|MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this
            ->createMock(ManagerRegistry::class);
        $this->documentManager = $this
            ->createMock(DocumentManager::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Customer::class)
            ->willReturn($this->documentManager);

        $this->repository = $this
            ->getMockBuilder(MongoCustomerRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();
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

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($customer);

        $result = $this->repository->findByEmail($email);

        $this->assertSame($customer, $result);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $email = $this->faker->email();

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        $result = $this->repository->findByEmail($email);

        $this->assertNull($result);
    }
}
