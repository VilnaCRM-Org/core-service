<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Application\Command\SchemathesisCustomerStatusSeeder;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;

final class SchemathesisCustomerStatusSeederTest extends UnitTestCase
{
    private StatusRepositoryInterface $statusRepository;
    private UlidFactory $ulidFactory;
    private SchemathesisCustomerStatusSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statusRepository = $this->createMock(StatusRepositoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->seeder = new SchemathesisCustomerStatusSeeder(
            $this->statusRepository,
            $this->ulidFactory
        );
    }

    public function testSeedStatusesCreatesNewStatuses(): void
    {
        $ulid = $this->createMock(Ulid::class);

        $this->statusRepository
            ->expects($this->exactly(3))
            ->method('find')
            ->willReturn(null);

        $this->ulidFactory
            ->expects($this->exactly(3))
            ->method('create')
            ->willReturn($ulid);

        $this->statusRepository
            ->expects($this->exactly(3))
            ->method('save');

        $result = $this->seeder->seedStatuses();

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('default', $result);
        $this->assertArrayHasKey('update', $result);
        $this->assertArrayHasKey('delete', $result);
        $this->assertContainsOnlyInstancesOf(CustomerStatus::class, $result);
    }

    public function testSeedStatusesUpdatesExistingStatuses(): void
    {
        $status = $this->createMock(CustomerStatus::class);

        $this->statusRepository
            ->expects($this->exactly(3))
            ->method('find')
            ->willReturn($status);

        $status->expects($this->exactly(3))->method('setValue');

        $this->statusRepository
            ->expects($this->exactly(3))
            ->method('save')
            ->with($status);

        $result = $this->seeder->seedStatuses();

        $this->assertCount(3, $result);
    }
}
