<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends BaseRepository<CustomerType>
 */
final class MongoTypeRepository extends BaseRepository implements
    TypeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerType::class);
        $this->documentManager = $this->getDocumentManager();
    }

    public function deleteByValue(string $value): void
    {
        $customerType = $this->findOneByCriteria(['value' => $value]);
        $this->documentManager->remove($customerType);
        $this->documentManager->flush();
    }
}
