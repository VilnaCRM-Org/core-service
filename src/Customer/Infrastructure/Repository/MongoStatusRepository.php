<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends BaseRepository<CustomerStatus>
 */
final class MongoStatusRepository extends BaseRepository implements
    StatusRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerStatus::class);
        $this->documentManager = $this->getDocumentManager();
    }

    public function deleteByValue(string $value): void
    {
        $customerStatus = $this->findOneByCriteria(['value' => $value]);
        $this->documentManager->remove($customerStatus);
        $this->documentManager->flush();
    }
}
