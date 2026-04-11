<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Database;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Infrastructure\Database\DatabaseCleaner;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use MongoDB\Database;

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
        $database = $this->createMock(Database::class);

        $collection->expects($this->exactly(3))->method('getCollectionName')->willReturn('customers');
        $collection->expects($this->exactly(3))->method('drop');

        $this->documentManager
            ->expects($this->exactly(3))
            ->method('getDocumentCollection')
            ->willReturn($collection);

        $this->documentManager
            ->expects($this->exactly(3))
            ->method('getDocumentDatabase')
            ->willReturn($database);

        $database
            ->expects($this->exactly(3))
            ->method('listCollectionNames')
            ->with(['filter' => ['name' => 'customers']])
            ->willReturn(new \ArrayIterator(['customers']));

        $this->documentManager
            ->expects($this->once())
            ->method('clear');

        $this->cleaner->dropCollections(Customer::class, CustomerType::class, CustomerStatus::class);
    }

    public function testDropCollectionsHandlesExceptions(): void
    {
        $collection = $this->createMock(Collection::class);
        $database = $this->createMock(Database::class);

        $this->documentManager
            ->expects($this->exactly(2))
            ->method('getDocumentCollection')
            ->willReturn($collection);

        $this->documentManager
            ->expects($this->exactly(2))
            ->method('getDocumentDatabase')
            ->willReturn($database);

        $collection
            ->expects($this->exactly(2))
            ->method('getCollectionName')
            ->willReturn('missing_collection');

        $collection
            ->expects($this->never())
            ->method('drop');

        $database
            ->expects($this->exactly(2))
            ->method('listCollectionNames')
            ->with(['filter' => ['name' => 'missing_collection']])
            ->willReturn(new \ArrayIterator([]));

        $this->documentManager
            ->expects($this->once())
            ->method('clear');

        $this->cleaner->dropCollections('NonExistent1', 'NonExistent2');
    }
}
