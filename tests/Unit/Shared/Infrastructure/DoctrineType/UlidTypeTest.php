<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\DoctrineType\UlidType;
use App\Tests\Unit\UnitTestCase;
use MongoDB\BSON\Binary;
use ReflectionClass;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidTypeTest extends UnitTestCase
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

        $ulid = new Ulid((string) $this->faker->ulid());

        $result = $ulidType->convertToDatabaseValue($ulid);
        $this->assertInstanceOf(Binary::class, $result);
        $this->assertSame($ulid->toBinary(), $result->getData());
    }

    public function testConvertToDatabaseValueWithNull(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $result = $ulidType->convertToDatabaseValue(null);
        $this->assertNull($result);
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

        $symfonyUlid = SymfonyUlid::fromString((string) $this->faker->ulid());
        $binary = new Binary($symfonyUlid->toBinary(), Binary::TYPE_GENERIC);

        $result = $ulidType->convertToPHPValue($binary);

        $this->assertInstanceOf(Ulid::class, $result);
        $this->assertSame((string) $symfonyUlid, (string) $result);
    }

    public function testConvertToPHPValueWithNonBinaryValue(): void
    {
        $ulidType = $this->getUlidTypeInstance();

        $symfonyUlid = SymfonyUlid::fromString((string) $this->faker->ulid());
        $binaryString = $symfonyUlid->toBinary();

        $result = $ulidType->convertToPHPValue($binaryString);

        $this->assertInstanceOf(Ulid::class, $result);
        $this->assertSame((string) $symfonyUlid, (string) $result);
    }

    public function testClosureToMongo(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $closureCode = $ulidType->closureToMongo();

        $this->assertStringContainsString('\Doctrine\ODM\MongoDB\Types\Type::getType(\'ulid\')', $closureCode);
        $this->assertStringContainsString('convertToDatabaseValue', $closureCode);
    }

    public function testClosureToPHP(): void
    {
        $ulidType = $this->getUlidTypeInstance();
        $closureCode = $ulidType->closureToPHP();

        $this->assertStringContainsString('\Doctrine\ODM\MongoDB\Types\Type::getType(\'ulid\')', $closureCode);
        $this->assertStringContainsString('convertToPHPValue', $closureCode);
    }

    private function getUlidTypeInstance(): UlidType
    {
        $reflection = new ReflectionClass(UlidType::class);
        return $reflection->newInstanceWithoutConstructor();
    }
}
