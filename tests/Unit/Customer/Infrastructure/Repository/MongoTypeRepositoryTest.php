<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Infrastructure\Repository\MongoTypeRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;

final class MongoTypeRepositoryTest extends UnitTestCase
{
    private ManagerRegistry $registry;

    private DocumentManager $documentManager;

    private MongoTypeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->documentManager = $this->createMock(DocumentManager::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(CustomerType::class)
            ->willReturn($this->documentManager);

        $this->repository = new MongoTypeRepository($this->registry);
    }

    public function testSave(): void
    {
        $customerType = $this->createMock(CustomerType::class);

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($customerType);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($customerType);
    }

    public function testDelete(): void
    {
        $customerType = $this->createMock(CustomerType::class);

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($customerType);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($customerType);
    }

    public function testDeleteByValue(): void
    {
        $value = 'active';
        $customerStatus = $this->createMock(CustomerType::class);

        $repository = $this->getMockBuilder(MongoTypeRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['value' => $value])
            ->willReturn($customerStatus);

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($customerStatus);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $repository->deleteByValue($value);
    }
}
