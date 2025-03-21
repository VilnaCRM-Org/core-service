<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use MongoDB\BSON\Binary;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidTransformerTest extends TestCase
{
    private UlidFactory $ulidFactory;
    private UlidTransformer $ulidTransformer;

    protected function setUp(): void
    {
        $this->ulidFactory = $this->createMock(
            UlidFactory::class
        );
        $this->ulidTransformer = new UlidTransformer($this->ulidFactory);
    }

    public function testToDatabaseValueWithUlidInstance(): void
    {
        $ulidString = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $ulid = new Ulid($ulidString);
        $expectedBinary = $ulid->toBinary();

        $result = $this->ulidTransformer->toDatabaseValue($ulid);

        $this->assertInstanceOf(Binary::class, $result);
        $this->assertSame($expectedBinary, $result->getData());
        $this->assertSame(Binary::TYPE_GENERIC, $result->getType());
    }

    public function testToDatabaseValueWithString(): void
    {
        $ulidString = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
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
}
