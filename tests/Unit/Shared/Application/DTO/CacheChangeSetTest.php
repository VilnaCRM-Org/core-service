<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\DTO;

use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheFieldChange;
use App\Tests\Unit\UnitTestCase;

final class CacheChangeSetTest extends UnitTestCase
{
    public function testCreateKeepsFieldChangesAndCanFindByField(): void
    {
        $emailChange = CacheFieldChange::create('email', 'old@example.com', 'new@example.com');
        $nameChange = CacheFieldChange::create('name', 'Old', 'New');
        $changeSet = CacheChangeSet::create($emailChange, $nameChange);

        self::assertCount(2, $changeSet);
        self::assertSame([$emailChange, $nameChange], iterator_to_array($changeSet));
        self::assertSame($emailChange, $changeSet->get('email'));
        self::assertNull($changeSet->get('missing'));
    }

    public function testFromDoctrineChangeSetNormalizesDoctrinePayload(): void
    {
        $changeSet = CacheChangeSet::fromDoctrineChangeSet([
            'email' => ['old@example.com', 'new@example.com'],
            'status' => ['old', null],
            'malformed' => [],
        ]);

        self::assertCount(3, $changeSet);
        self::assertSame('old@example.com', $changeSet->get('email')?->oldValue());
        self::assertSame('new@example.com', $changeSet->get('email')?->newValue());
        self::assertSame('old', $changeSet->get('status')?->oldValue());
        self::assertNull($changeSet->get('status')?->newValue());
        self::assertNull($changeSet->get('malformed')?->oldValue());
        self::assertNull($changeSet->get('malformed')?->newValue());
    }

    public function testEmptyChangeSetHasNoChanges(): void
    {
        $changeSet = CacheChangeSet::empty();

        self::assertCount(0, $changeSet);
        self::assertSame([], iterator_to_array($changeSet));
        self::assertNull($changeSet->get('email'));
    }
}
