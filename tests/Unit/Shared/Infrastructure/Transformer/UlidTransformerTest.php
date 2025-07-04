<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidTransformerTest extends UnitTestCase
{
    private UlidFactory $ulidFactory;
    private UlidTransformer $ulidTransformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->ulidTransformer = new UlidTransformer($this->ulidFactory);
    }

    public function testTransformToSymfonyUlid(): void
    {
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);

        $result = $this->ulidTransformer->transformToSymfonyUlid($ulid);

        $this->assertInstanceOf(SymfonyUlid::class, $result);
        $this->assertSame($ulidString, (string) $result);
    }

    public function testTransformFromString(): void
    {
        $ulidString = (string) $this->faker->ulid();
        $expectedUlid = new Ulid($ulidString);

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with($ulidString)
            ->willReturn($expectedUlid);

        $result = $this->ulidTransformer->transformFromString($ulidString);

        $this->assertSame($expectedUlid, $result);
    }

    public function testTransformFromStringWithInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ULID format');

        $this->ulidTransformer->transformFromString('invalid-ulid');
    }

    public function testToDatabaseValueWithUlidInstance(): void
    {
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);

        $result = $this->ulidTransformer->toDatabaseValue($ulid);

        $this->assertNotNull($result);
        $this->assertTrue(is_object($result));
    }

    public function testToDatabaseValueWithString(): void
    {
        $ulidString = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidString);

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with($ulidString)
            ->willReturn($ulid);

        $result = $this->ulidTransformer->toDatabaseValue($ulidString);

        $this->assertNotNull($result);
        $this->assertTrue(is_object($result));
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
        $symfonyUlid = new SymfonyUlid();
        $expectedUlid = new Ulid((string) $symfonyUlid);

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with($symfonyUlid->toBase32())
            ->willReturn($expectedUlid);

        $result = $this->ulidTransformer
            ->transformFromSymfonyUlid($symfonyUlid);

        $this->assertSame($expectedUlid, $result);
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

    public function testToPhpValueReturnsNullForNullValue(): void
    {
        $result = $this->ulidTransformer->toPhpValue(null);

        $this->assertNull($result);
    }

    public function testToPhpValueReturnsNullForUnsupportedType(): void
    {
        $result = $this->ulidTransformer->toPhpValue(123);

        $this->assertNull($result);
    }
}
