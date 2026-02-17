<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
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

    #[Override]
    public function deleteByValue(string $value): void
    {
        $customerType = $this->findOneByCriteria(['value' => $value]);
        $this->documentManager->remove($customerType);
        $this->documentManager->flush();
    }
}
