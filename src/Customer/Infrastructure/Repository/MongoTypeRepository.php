<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

final class MongoTypeRepository extends ServiceDocumentRepository implements
    TypeRepositoryInterface
{
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerType::class);
        $this->documentManager = $this->getDocumentManager();
    }

    /**
     * @param CustomerType $customerType
     */
    public function save(object $customerType): void
    {
        $this->documentManager->persist($customerType);
        $this->documentManager->flush();
    }
}
