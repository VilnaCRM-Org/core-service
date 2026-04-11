<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Database;

use Doctrine\ODM\MongoDB\DocumentManager;

final readonly class DatabaseCleaner
{
    public function __construct(
        private DocumentManager $documentManager
    ) {
    }

    public function dropCollections(string ...$collections): void
    {
        foreach ($collections as $collection) {
            $this->dropCollection($collection);
        }

        $this->documentManager->clear();
    }

    private function dropCollection(string $collection): void
    {
        $documentCollection = $this->documentManager->getDocumentCollection($collection);
        $database = $this->documentManager->getDocumentDatabase($collection);
        $existingCollections = iterator_to_array(
            $database->listCollectionNames([
                'filter' => ['name' => $documentCollection->getCollectionName()],
            ])
        );

        if ($existingCollections === []) {
            return;
        }

        $documentCollection->drop();
    }
}
