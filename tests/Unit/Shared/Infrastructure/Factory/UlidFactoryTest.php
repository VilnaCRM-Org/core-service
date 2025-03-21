<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use PHPUnit\Framework\TestCase;

final class UlidFactoryTest extends TestCase
{
    public function testCreateReturnsUlidInstance(): void
    {
        $ulidString = '01ABCDEFGHJKMNPQRSTVWXYZAB';
        $factory = new UlidFactory();

        $ulid = $factory->create($ulidString);

        $this->assertInstanceOf(Ulid::class, $ulid, 'The created object should be an instance of Ulid.');
        $this->assertSame($ulidString, (string) $ulid, 'The Ulid string should match the input.');
    }
}
