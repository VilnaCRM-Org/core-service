<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Repository\CustomerStatusRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class MongoDBCustomerStatusRepository extends ServiceDocumentRepository implements CustomerStatusRepositoryInterface
{
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerType::class);
        $this->documentManager = $this->getDocumentManager();
    }

    /**
     * @param CustomerStatus $customerStatus
     */
    public function save(object $customerStatus): void
    {
        $this->documentManager->persist($customerStatus);
        $this->documentManager->flush();
    }
}
