<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use App\Shared\Infrastructure\Database\DatabaseCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-schemathesis-data',
    description: self::COMMAND_DESCRIPTION
)]
final class SeedSchemathesisDataCommand extends Command
{
    private const COMMAND_DESCRIPTION = 'Seed schemathesis reference data.';

    private const COLLECTIONS_TO_DROP = [
        'Customer',
        'CustomerType',
        'CustomerStatus',
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
        $io = new SymfonyStyle($input, $output);

        $this->databaseCleaner->dropCollections(self::COLLECTIONS_TO_DROP);

        // Seed types and statuses first
        $types = $this->typeSeeder->seedTypes();
        $statuses = $this->statusSeeder->seedStatuses();

        // Seed customers using the seeded types and statuses
        $this->customerSeeder->seedCustomers($types, $statuses);

        $io->success('Schemathesis reference data has been seeded.');

        return Command::SUCCESS;
    }
}
