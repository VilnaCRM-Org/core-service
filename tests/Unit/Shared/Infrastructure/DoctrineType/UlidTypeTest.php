<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\DoctrineType\UlidType;
use App\Tests\Unit\UnitTestCase;
use MongoDB\BSON\Binary;
use ReflectionClass;

final class UlidTypeTest extends UnitTestCase
{
    private UlidType $ulidType;

    protected function setUp(): void
    {
        parent::setUp();
        $reflectionClass = new ReflectionClass(UlidType::class);
        $this->ulidType = $reflectionClass->newInstanceWithoutConstructor();
    }

    public function testConvertToDatabaseValueWithUlid(): void
    {
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);

        $result = $this->ulidType->convertToDatabaseValue($ulid);

        $this->assertInstanceOf(Binary::class, $result);
        $this->assertSame(Binary::TYPE_GENERIC, $result->getType());
    }

    public function testConvertToDatabaseValueWithString(): void
    {
        $ulidString = (string) $this->faker->ulid();

        $result = $this->ulidType->convertToDatabaseValue($ulidString);

        $this->assertInstanceOf(Binary::class, $result);
        $this->assertSame(Binary::TYPE_GENERIC, $result->getType());
    }

    public function testConvertToDatabaseValueWithNull(): void
    {
        $result = $this->ulidType->convertToDatabaseValue(null);

        $this->assertNull($result);
    }

    public function testConvertToPHPValueWithBinary(): void
    {
        $binary = new Binary('some binary data', Binary::TYPE_GENERIC);

        $result = $this->ulidType->convertToPHPValue($binary);

        $this->assertInstanceOf(Ulid::class, $result);
    }

    public function testConvertToPHPValueWithString(): void
    {
        $ulidString = (string) $this->faker->ulid();

        $result = $this->ulidType->convertToPHPValue($ulidString);

        $this->assertInstanceOf(Ulid::class, $result);
    }

    public function testConvertToPHPValueWithNull(): void
    {
        $result = $this->ulidType->convertToPHPValue(null);

        $this->assertNull($result);
    }
}
