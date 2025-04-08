<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<CustomerStatus>
 */
final class MongoStatusRepository extends ServiceDocumentRepository implements
    StatusRepositoryInterface
{
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerStatus::class);
        $this->documentManager = $this->getDocumentManager();
    }

    public function save(CustomerStatus $customerStatus): void
    {
        $this->documentManager->persist($customerStatus);
        $this->documentManager->flush();
    }

    public function delete(CustomerStatus $customerStatus): void
    {
        $this->documentManager->remove($customerStatus);
        $this->documentManager->flush();
    }

    public function deleteByValue(string $value): void
    {
        $customerStatus = $this->findOneBy(['value' => $value]);
        $this->documentManager->remove($customerStatus);
        $this->documentManager->flush();
    }
}
