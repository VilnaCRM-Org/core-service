<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Application\Command\SchemathesisCustomerTypeSeeder;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;

final class SchemathesisCustomerTypeSeederTest extends UnitTestCase
{
    private TypeRepositoryInterface $typeRepository;
    private UlidFactory $ulidFactory;
    private SchemathesisCustomerTypeSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->typeRepository = $this->createMock(TypeRepositoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->seeder = new SchemathesisCustomerTypeSeeder(
            $this->typeRepository,
            $this->ulidFactory
        );
    }

    public function testSeedTypesCreatesNewTypes(): void
    {
        $ulid = $this->createMock(Ulid::class);

        $this->typeRepository
            ->expects($this->exactly(3))
            ->method('find')
            ->willReturn(null);

        $this->ulidFactory
            ->expects($this->exactly(3))
            ->method('create')
            ->willReturn($ulid);

        $this->typeRepository
            ->expects($this->exactly(3))
            ->method('save');

        $result = $this->seeder->seedTypes();

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('default', $result);
        $this->assertArrayHasKey('update', $result);
        $this->assertArrayHasKey('delete', $result);
        $this->assertContainsOnlyInstancesOf(CustomerType::class, $result);
    }

    public function testSeedTypesUpdatesExistingTypes(): void
    {
        $type = $this->createMock(CustomerType::class);

        $this->typeRepository
            ->expects($this->exactly(3))
            ->method('find')
            ->willReturn($type);

        $type->expects($this->exactly(3))->method('setValue');

        $this->typeRepository
            ->expects($this->exactly(3))
            ->method('save')
            ->with($type);

        $result = $this->seeder->seedTypes();

        $this->assertCount(3, $result);
    }
}
