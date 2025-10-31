<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command;

use App\Shared\Application\Command\SchemathesisCustomerSeeder;
use App\Shared\Application\Command\SchemathesisCustomerStatusSeeder;
use App\Shared\Application\Command\SchemathesisCustomerTypeSeeder;
use App\Shared\Application\Command\SeedSchemathesisDataCommand;
use App\Shared\Infrastructure\Database\DatabaseCleaner;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SeedSchemathesisDataCommandTest extends UnitTestCase
{
    private SchemathesisCustomerTypeSeeder $typeSeeder;
    private SchemathesisCustomerStatusSeeder $statusSeeder;
    private SchemathesisCustomerSeeder $customerSeeder;
    private DatabaseCleaner $databaseCleaner;
    private SeedSchemathesisDataCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->typeSeeder = $this->createMock(SchemathesisCustomerTypeSeeder::class);
        $this->statusSeeder = $this->createMock(SchemathesisCustomerStatusSeeder::class);
        $this->customerSeeder = $this->createMock(SchemathesisCustomerSeeder::class);
        $this->databaseCleaner = $this->createMock(DatabaseCleaner::class);

        $this->command = new SeedSchemathesisDataCommand(
            $this->typeSeeder,
            $this->statusSeeder,
            $this->customerSeeder,
            $this->databaseCleaner
        );
    }

    public function testExecuteSeedsData(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->databaseCleaner
            ->expects($this->once())
            ->method('dropCollections')
            ->with(['Customer', 'CustomerType', 'CustomerStatus']);

        $types = ['default' => $this->createMock(\App\Core\Customer\Domain\Entity\CustomerType::class)];
        $statuses = ['default' => $this->createMock(\App\Core\Customer\Domain\Entity\CustomerStatus::class)];

        $this->typeSeeder
            ->expects($this->once())
            ->method('seedTypes')
            ->willReturn($types);

        $this->statusSeeder
            ->expects($this->once())
            ->method('seedStatuses')
            ->willReturn($statuses);

        $this->customerSeeder
            ->expects($this->once())
            ->method('seedCustomers')
            ->with($types, $statuses);

        $method = new \ReflectionMethod($this->command, 'execute');
        $method->setAccessible(true);
        $result = $method->invoke($this->command, $input, $output);

        $this->assertEquals(0, $result);
    }
}
