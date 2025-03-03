<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Repository\CustomerTypeRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class MongoDBCustomerTypeRepository extends ServiceDocumentRepository implements CustomerTypeRepositoryInterface
{
    private DocumentManager $documentManager;

    /**
     * @param CustomerType $customerType
     */
    public function save(object $customerType): void
    {
        $this->documentManager->persist($customerType);
        $this->documentManager->flush();
    }
}
