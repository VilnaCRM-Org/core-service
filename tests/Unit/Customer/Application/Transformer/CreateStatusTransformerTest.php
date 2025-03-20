<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Customer\Application\Command\CreateStatusCommand;
use App\Customer\Application\Transformer\CreateStatusTransformer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Factory\StatusFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory as UlidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UlidFactory;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateStatusTransformerTest extends UnitTestCase
{
    private StatusFactoryInterface|MockObject $statusFactory;
    private UlidTransformer $transformer;
    private UlidFactory $symfonyUlidFactory;
    private UlidTransformer|MockObject $ulidTransformerMock;
    private UlidFactory|MockObject $ulidFactoryMock;
    private CreateStatusTransformer $createStatusTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusFactory = $this->createMock(StatusFactoryInterface::class);
        $this->transformer = new UlidTransformer(new UlidFactoryInterface());
        $this->symfonyUlidFactory = new UlidFactory();
        $this->ulidTransformerMock = $this->createMock(UlidTransformer::class);
        $this->ulidFactoryMock = $this->createMock(UlidFactory::class);
        $this->createStatusTransformer = new CreateStatusTransformer(
            $this->statusFactory,
            $this->ulidTransformerMock,
            $this->ulidFactoryMock
        );
    }

    public function testTransform(): void
    {
        $value = $this->faker->word();
        $command = new CreateStatusCommand($value);
        $status = $this->createMock(CustomerStatus::class);

        $this->setExpectations($status, $value);

        $result = $this->createStatusTransformer->transform($command);

        $this->assertSame($status, $result);
    }

    private function setExpectations(CustomerStatus $status, string $value): void
    {
        $ulidObject = $this->createMock(Ulid::class);

        $this->ulidFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->symfonyUlidFactory->create());

        $this->ulidTransformerMock->expects($this->once())
            ->method('transformFromSymfonyUlid')
            ->willReturn($ulidObject);

        $this->statusFactory->expects($this->once())
            ->method('create')
            ->with($value, $ulidObject)
            ->willReturn($status);
    }
} 