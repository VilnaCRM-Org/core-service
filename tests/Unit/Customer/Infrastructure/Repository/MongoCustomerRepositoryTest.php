<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Infrastructure\Repository\MongoCustomerRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;

final class MongoCustomerRepositoryTest extends UnitTestCase
{
    private ManagerRegistry $registry;

    private DocumentManager $documentManager;

    private MongoCustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->documentManager = $this->createMock(DocumentManager::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Customer::class)
            ->willReturn($this->documentManager);

        $this->repository = new MongoCustomerRepository($this->registry);
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
}
