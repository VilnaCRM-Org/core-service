<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Converter\UlidConverter;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidValidator;
use App\Tests\Unit\UnitTestCase;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidTransformerTest extends UnitTestCase
{
    private UlidFactory $ulidFactory;
    private UlidTransformer $ulidTransformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $validator = new UlidValidator();
        $converter = new UlidConverter($this->ulidFactory);
        $this->ulidTransformer = new UlidTransformer($this->ulidFactory, $validator, $converter);
    }

    public function testToDatabaseValueWithUlidInstance(): void
    {
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);
        $expectedBinary = $ulid->toBinary();

        $result = $this->ulidTransformer->toDatabaseValue($ulid);

        $this->assertInstanceOf(Binary::class, $result);
        $this->assertSame($expectedBinary, $result->getData());
        $this->assertSame(Binary::TYPE_GENERIC, $result->getType());
    }

    public function testToDatabaseValueWithString(): void
    {
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);
        $expectedBinary = $ulid->toBinary();

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with($ulidString)
            ->willReturn($ulid);

        $result = $this->ulidTransformer->toDatabaseValue($ulidString);

        $this->assertInstanceOf(Binary::class, $result);
        $this->assertSame($expectedBinary, $result->getData());
        $this->assertSame(Binary::TYPE_GENERIC, $result->getType());
    }

    public function testToPhpValueWithBinaryString(): void
    {
        $ulid = new SymfonyUlid();
        $binaryData = $ulid->toBinary();

        $ulidMock = $this->createMock(Ulid::class);
        $ulidMock->method('__toString')->willReturn((string) $ulid);

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with((string) $ulid)
            ->willReturn($ulidMock);

        $result = $this->ulidTransformer->toPhpValue($binaryData);

        $this->assertInstanceOf(Ulid::class, $result);
    }

    public function testToPhpValueWithSymfonyUlidInstance(): void
    {
        $symfonyUlid = $this->createMock(SymfonyUlid::class);
        $ulid = $this->createMock(Ulid::class);

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with((string) $symfonyUlid)
            ->willReturn($ulid);

        $result = $this->ulidTransformer->toPhpValue($symfonyUlid);

        $this->assertInstanceOf(Ulid::class, $result);
        $this->assertSame($ulid, $result);
    }

    public function testTransformFromSymfonyUlid(): void
    {
        $symfonyUlid = $this->createMock(SymfonyUlid::class);
        $ulid = $this->createMock(Ulid::class);

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with((string) $symfonyUlid)
            ->willReturn($ulid);

        $result = $this->ulidTransformer
            ->transformFromSymfonyUlid($symfonyUlid);

        $this->assertInstanceOf(Ulid::class, $result);
    }

    public function testToDatabaseValueReturnsNullForNullValue(): void
    {
        $this->ulidFactory->expects($this->never())->method('create');

        $result = $this->ulidTransformer->toDatabaseValue(null);

        $this->assertNull($result);
    }

    public function testToDatabaseValueReturnsNullForInvalidUlidString(): void
    {
        $invalidUlid = 'invalid-ulid-string';
        $this->ulidFactory->expects($this->never())->method('create');

        $result = $this->ulidTransformer->toDatabaseValue($invalidUlid);

        $this->assertNull($result);
    }
}
