<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Customer\Application\Command\CreateTypeCommand;
use App\Customer\Application\Transformer\CreateTypeTransformer;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\TypeFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory as UlidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UlidFactory;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateTypeTransformerTest extends UnitTestCase
{
    private TypeFactoryInterface|MockObject $typeFactory;
    private UlidTransformer $transformer;
    private UlidFactory $symfonyUlidFactory;
    private UlidTransformer|MockObject $ulidTransformerMock;
    private UlidFactory|MockObject $ulidFactoryMock;
    private CreateTypeTransformer $createTypeTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typeFactory = $this->createMock(TypeFactoryInterface::class);
        $this->transformer = new UlidTransformer(new UlidFactoryInterface());
        $this->symfonyUlidFactory = new UlidFactory();
        $this->ulidTransformerMock = $this->createMock(UlidTransformer::class);
        $this->ulidFactoryMock = $this->createMock(UlidFactory::class);
        $this->createTypeTransformer = new CreateTypeTransformer(
            $this->typeFactory,
            $this->ulidTransformerMock,
            $this->ulidFactoryMock
        );
    }

    public function testTransform(): void
    {
        $value = $this->faker->word();
        $command = new CreateTypeCommand($value);
        $type = $this->createMock(CustomerType::class);

        $this->setExpectations($type, $value);

        $result = $this->createTypeTransformer->transform($command);

        $this->assertSame($type, $result);
    }

    private function setExpectations(CustomerType $type, string $value): void
    {
        $ulidObject = $this->createMock(Ulid::class);

        $this->ulidFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->symfonyUlidFactory->create());

        $this->ulidTransformerMock->expects($this->once())
            ->method('transformFromSymfonyUlid')
            ->willReturn($ulidObject);

        $this->typeFactory->expects($this->once())
            ->method('create')
            ->with($value, $ulidObject)
            ->willReturn($type);
    }
} 