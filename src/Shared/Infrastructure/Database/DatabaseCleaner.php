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

    /**
     * @param array<string> $collections
     */
    public function dropCollections(array $collections): void
    {
        foreach ($collections as $collection) {
            $this->dropCollection($collection);
        }

        $this->documentManager->clear();
    }

    private function dropCollection(string $collection): void
    {
        try {
            $this->documentManager->getDocumentCollection($collection)->drop();
        } catch (\Exception $exception) {
            unset($exception);
            // Collection might not exist yet, that's okay - silently ignore
        }
    }
}
