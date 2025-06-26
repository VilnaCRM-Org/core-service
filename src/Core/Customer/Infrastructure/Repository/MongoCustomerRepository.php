<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;

/**
 * @extends BaseRepository<Customer>
 */
final class MongoCustomerRepository extends BaseRepository implements
    CustomerRepositoryInterface
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
        $this->documentManager = $this->getDocumentManager();
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->findOneByCriteria(['email' => $email]);
    }
}
