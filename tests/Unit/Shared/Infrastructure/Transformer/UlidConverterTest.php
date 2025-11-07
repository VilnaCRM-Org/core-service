<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidConverter;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidConverterTest extends UnitTestCase
{
    private UlidConverter $converter;
    private UlidFactory $ulidFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->converter = new UlidConverter($this->ulidFactory);
    }

    public function testToUlidWithUlidInstance(): void
    {
        $ulid = new Ulid((string) $this->faker->ulid());

        $result = $this->converter->toUlid($ulid);

        $this->assertSame($ulid, $result);
    }

    public function testToUlidWithString(): void
    {
        $ulidString = (string) $this->faker->ulid();
        $expectedUlid = new Ulid($ulidString);

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with($ulidString)
            ->willReturn($expectedUlid);

        $result = $this->converter->toUlid($ulidString);

        $this->assertSame($expectedUlid, $result);
    }

    public function testFromBinaryWithSymfonyUlidInstance(): void
    {
        $symfonyUlid = new SymfonyUlid();

        $result = $this->converter->fromBinary($symfonyUlid);

        $this->assertSame($symfonyUlid, $result);
    }

    public function testFromBinaryWithBinaryString(): void
    {
        $symfonyUlid = new SymfonyUlid();
        $binaryData = $symfonyUlid->toBinary();

        $result = $this->converter->fromBinary($binaryData);

        $this->assertInstanceOf(SymfonyUlid::class, $result);
        $this->assertEquals((string) $symfonyUlid, (string) $result);
    }
}
