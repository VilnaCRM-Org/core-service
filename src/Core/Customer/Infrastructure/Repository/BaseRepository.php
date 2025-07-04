<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of object
 *
 * @extends ServiceDocumentRepository<T>
 */
abstract class BaseRepository extends ServiceDocumentRepository
{
    protected DocumentManager $documentManager;

    public function __construct(
        ManagerRegistry $registry,
        string $documentClass
    ) {
        parent::__construct($registry, $documentClass);
        $this->documentManager = $this->getDocumentManager();
    }

    /**
     * Persist and flush an entity.
     */
    public function save(object $entity): void
    {
        $this->documentManager->persist($entity);
        $this->documentManager->flush();
    }

    /**
     * Remove and flush an entity.
     */
    public function delete(object $entity): void
    {
        $this->documentManager->remove($entity);
        $this->documentManager->flush();
    }

    /**
     * Find one by arbitrary criteria.
     *
     * @param array<string, string> $criteria
     *
     * @return T|null
     */
    public function findOneByCriteria(array $criteria): ?object
    {
        return $this->findOneBy($criteria);
    }
}
