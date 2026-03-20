<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @template T of object
 *
 * @extends ServiceDocumentRepository<T>
 */
abstract class BaseRepository extends ServiceDocumentRepository
{
    protected DocumentManager $documentManager;

    /**
     * Persist and flush an entity.
     *
     * @param T $entity
     */
    public function save(object $entity): void
    {
        $this->documentManager->persist($entity);
        $this->documentManager->flush();
    }

    /**
     * Remove and flush an entity.
     *
     * @param T $entity
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
