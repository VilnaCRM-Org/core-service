<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class MongoDBCustomerRepository extends ServiceDocumentRepository implements CustomerRepositoryInterface
{

    private DocumentManager $documentManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
        $this->documentManager = $this->getDocumentManager();
    }

    /**
     * @param Customer $customer
     */
    public function save(object $customer): void
    {
        $this->documentManager->persist($customer);
        $this->documentManager->flush();
    }

    /**
     * @param Customer $customer
     */
    public function delete(object $customer): void
    {
        $this->documentManager->remove($customer);
        $this->documentManager->flush();
    }

    /**
     * @param string $id
     *
     * @return Customer|null
     */
    public function find(mixed $id, ?int $lockMode = null, ?int $lockVersion = null): ?object
    {
        return $this->find($id);
    }
}
