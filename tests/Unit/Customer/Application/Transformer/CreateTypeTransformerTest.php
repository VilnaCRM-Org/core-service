<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Transformer\CreateTypeTransformer;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\TypeFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class CreateTypeTransformerTest extends UnitTestCase
{
    private TypeFactoryInterface|MockObject $typeFactory;
    private UlidTransformer|MockObject $ulidTransformer;
    private UlidFactory|MockObject $ulidFactory;
    private CreateTypeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typeFactory = $this->createMock(TypeFactoryInterface::class);
        $this->ulidTransformer = $this->createMock(UlidTransformer::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->transformer = new CreateTypeTransformer(
            $this->typeFactory,
            $this->ulidTransformer,
            $this->ulidFactory
        );
    }

    public function testTransformCreatesTypeWithGeneratedUlid(): void
    {
        // Arrange
        $value = $this->faker->word();
        $symfonyUlid = $this->createMock(SymfonyUlid::class);
        $valueObjectUlid = $this->createMock(Ulid::class);
        $expectedType = $this->createMock(CustomerType::class);

        // Expect the factory to generate a Symfony ULID
        $this->ulidFactory->expects(self::once())
            ->method('create')
            ->willReturn($symfonyUlid);

        // Expect transformer to convert Symfony ULID to our Ulid VO
        $this->ulidTransformer->expects(self::once())
            ->method('transformFromSymfonyUlid')
            ->with(self::identicalTo($symfonyUlid))
            ->willReturn($valueObjectUlid);

        // Expect TypeFactory to be called with the raw value and the transformed ULID
        $this->typeFactory->expects(self::once())
            ->method('create')
            ->with(
                self::equalTo($value),
                self::identicalTo($valueObjectUlid)
            )
            ->willReturn($expectedType);

        // Act
        $result = $this->transformer->transform($value);

        // Assert
        $this->assertSame($expectedType, $result);
    }
}
