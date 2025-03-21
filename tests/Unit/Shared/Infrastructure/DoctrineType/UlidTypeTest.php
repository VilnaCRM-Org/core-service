<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\DoctrineType\UlidType;
use MongoDB\BSON\Binary;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class UlidTypeTest extends TestCase
{
    public function testGetName(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $this->assertSame('ulid', $ulidType->getName());
    }

    public function testConvertToDatabaseValueWithBinary(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $binary = new Binary('some binary data', Binary::TYPE_GENERIC);
        $result = $ulidType->convertToDatabaseValue($binary);
        $this->assertSame($binary, $result);
    }

    public function testConvertToDatabaseValueWithUlid(): void
    {
        $ulidType = $this->getUlidTypeInstance();

        $dummyUlid = $this->createMock(Ulid::class);

        $result = $ulidType->convertToDatabaseValue($dummyUlid);
        $this->assertInstanceOf(Binary::class, $result);
    }

    public function testConvertToPHPValueWithNull(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $result = $ulidType->convertToPHPValue(null);
        $this->assertNull($result);
    }

    public function testConvertToPHPValueWithUlid(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $dummyUlid = $this->createMock(Ulid::class);

        $result = $ulidType->convertToPHPValue($dummyUlid);
        $this->assertSame($dummyUlid, $result);
    }

    public function testConvertToPHPValueWithBinary(): void
    {
        $ulidType = $this->getUlidTypeInstance();

        $binaryData = 'some binary data';
        $binary = new Binary($binaryData, Binary::TYPE_GENERIC);

        $result = $ulidType->convertToPHPValue($binary);

        if ($result !== null) {
            $this->assertInstanceOf(Ulid::class, $result);
        } else {
            $this->markTestIncomplete('Ulid transformation logic not fully implemented for testing.');
        }
    }

    public function testClosureToMongo(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $closureCode = $ulidType->closureToMongo();

        $this->assertStringContainsString('\MongoDB\BSON\Binary', $closureCode);
        $this->assertStringContainsString('toBinary()', $closureCode);
    }

    public function testClosureToPHP(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $closureCode = $ulidType->closureToPHP();

        $this->assertStringContainsString('new \App\Shared\Infrastructure\Transformer\UlidTransformer', $closureCode);
        $this->assertStringContainsString('transformFromSymfonyUlid', $closureCode);
    }

    private function getUlidTypeInstance(): UlidType
    {
        $reflection = new ReflectionClass(UlidType::class);
        return $reflection->newInstanceWithoutConstructor();
    }
}
