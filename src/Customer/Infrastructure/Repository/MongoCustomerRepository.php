<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerInterface;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @extends ServiceDocumentRepository<Customer>
 */
final class MongoCustomerRepository extends ServiceDocumentRepository implements
    CustomerRepositoryInterface
{
    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
        $this->documentManager = $this->getDocumentManager();
    }

    public function save(Customer $customer): void
    {
        $this->documentManager->persist($customer);
        $this->documentManager->flush();
    }


    public function findByEmail(string $email): ?CustomerInterface
    {
        return $this->findOneBy(['email' => $email]);
    }
}
