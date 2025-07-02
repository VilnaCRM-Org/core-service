<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Transformer\CreateStatusTransformer;
use App\Core\Customer\Application\Transformer\StatusTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Factory\StatusFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class CreateStatusTransformerTest extends UnitTestCase
{
    private StatusFactoryInterface|MockObject $statusFactory;
    private UlidTransformer|MockObject $ulidTransformer;
    private UlidFactory|MockObject $ulidFactory;
    private StatusTransformerInterface $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusFactory = $this->createMock(StatusFactoryInterface::class);
        $this->ulidTransformer = $this->createMock(UlidTransformer::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->transformer = new CreateStatusTransformer(
            $this->statusFactory,
            $this->ulidTransformer,
            $this->ulidFactory
        );
    }

    public function testTransformCreatesStatusWithGeneratedUlid(): void
    {
        [$value, $ulid, $domainUlid, $status] = $this->createTestFixtures();

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($ulid);

        $this->ulidTransformer
            ->expects($this->once())
            ->method('transformFromSymfonyUlid')
            ->with(self::identicalTo($ulid))
            ->willReturn($domainUlid);

        $this->statusFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                self::equalTo($value),
                self::identicalTo($domainUlid)
            )
            ->willReturn($status);

        $result = $this->transformer->transform($value);

        $this->assertSame($status, $result);
    }

    /**
     * @return (MockObject&CustomerStatus|MockObject&SymfonyUlid|MockObject&Ulid|string)[]
     *
     * @psalm-return list{string, MockObject&SymfonyUlid, MockObject&Ulid, MockObject&CustomerStatus}
     */
    private function createTestFixtures(): array
    {
        $value = $this->faker->word();
        $symfonyUlid = $this->createMock(SymfonyUlid::class);
        $valueObjectUlid = $this->createMock(Ulid::class);
        $expectedStatus = $this->createMock(CustomerStatus::class);
        return [$value, $symfonyUlid, $valueObjectUlid, $expectedStatus];
    }
}
