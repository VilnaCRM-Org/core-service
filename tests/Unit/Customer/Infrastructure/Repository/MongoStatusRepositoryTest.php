<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Infrastructure\Repository\MongoStatusRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;

final class MongoStatusRepositoryTest extends UnitTestCase
{
    private ManagerRegistry $registry;

    private DocumentManager $documentManager;

    private MongoStatusRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->documentManager = $this->createMock(DocumentManager::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(CustomerStatus::class)
            ->willReturn($this->documentManager);

        $this->repository = new MongoStatusRepository($this->registry);
    }

    public function testSave(): void
    {
        $customerStatus = $this->createMock(CustomerStatus::class);

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($customerStatus);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->save($customerStatus);
    }

    public function testDelete(): void
    {
        $customerStatus = $this->createMock(CustomerStatus::class);

        $this->documentManager->expects($this->once())
            ->method('remove')
            ->with($customerStatus);

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->repository->delete($customerStatus);
    }
}
