<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Infrastructure\Database\DatabaseCleaner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SeedSchemathesisDataCommand extends Command
{
    private const COLLECTIONS_TO_DROP = [
        Customer::class,
        CustomerType::class,
        CustomerStatus::class,
    ];

    public function __construct(
        private readonly SchemathesisCustomerTypeSeeder $typeSeeder,
        private readonly SchemathesisCustomerStatusSeeder $statusSeeder,
        private readonly SchemathesisCustomerSeeder $customerSeeder,
        private readonly DatabaseCleaner $databaseCleaner,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        unset($input);

        $this->databaseCleaner->dropCollections(...self::COLLECTIONS_TO_DROP);

        // Seed types and statuses first
        $types = $this->typeSeeder->seedTypes();
        $statuses = $this->statusSeeder->seedStatuses();

        // Seed customers using the seeded types and statuses
        $this->customerSeeder->seedCustomers($types, $statuses);

        $output->writeln('<info>Schemathesis reference data has been seeded.</info>');

        return Command::SUCCESS;
    }
}
