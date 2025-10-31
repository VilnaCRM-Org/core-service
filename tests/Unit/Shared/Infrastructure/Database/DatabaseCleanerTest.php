<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Database;

use App\Shared\Infrastructure\Database\DatabaseCleaner;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;

final class DatabaseCleanerTest extends UnitTestCase
{
    private DocumentManager $documentManager;
    private DatabaseCleaner $cleaner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->cleaner = new DatabaseCleaner($this->documentManager);
    }

    public function testDropCollectionsSuccessfully(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->exactly(3))->method('drop');

        $this->documentManager
            ->expects($this->exactly(3))
            ->method('getDocumentCollection')
            ->willReturn($collection);

        $this->documentManager
            ->expects($this->once())
            ->method('clear');

        $this->cleaner->dropCollections(['Customer', 'CustomerType', 'CustomerStatus']);
    }

    public function testDropCollectionsHandlesExceptions(): void
    {
        $this->documentManager
            ->expects($this->exactly(2))
            ->method('getDocumentCollection')
            ->willThrowException(new \Exception('Collection does not exist'));

        $this->documentManager
            ->expects($this->once())
            ->method('clear');

        // Should not throw exception
        $this->cleaner->dropCollections(['NonExistent1', 'NonExistent2']);

        $this->assertTrue(true);
    }
}
