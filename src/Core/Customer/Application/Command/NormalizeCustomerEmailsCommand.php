<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Command;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerStreamRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Backfill command that canonicalises customer emails to lowercase.
 *
 * Email matching is case-insensitive across the cache, the unique index and
 * the validator. Legacy rows persisted with mixed-case emails are migrated
 * here so the stored value matches the canonical lowercase form.
 *
 * Records whose lowercase email already belongs to a different customer would
 * violate the unique index; those are skipped and reported instead of
 * crashing the migration.
 */
#[AsCommand(
    name: 'customer:emails:normalize',
    description: 'Backfill customer emails to canonical lowercase.',
)]
final class NormalizeCustomerEmailsCommand extends Command
{
    public function __construct(
        private readonly CustomerStreamRepositoryInterface $customerRepository,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $normalized = 0;
        $skipped = 0;

        foreach ($this->customerRepository->findAllIterable() as $customer) {
            $result = $this->normalizeCustomer($customer, $io);

            $normalized += $result['normalized'];
            $skipped += $result['skipped'];
        }

        $io->success(sprintf(
            'Normalized %d customer email(s); skipped %d conflicting record(s).',
            $normalized,
            $skipped
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array{normalized: int, skipped: int}
     */
    private function normalizeCustomer(
        Customer $customer,
        SymfonyStyle $io
    ): array {
        $currentEmail = $customer->getEmail();
        $canonicalEmail = strtolower($currentEmail);

        if ($currentEmail === $canonicalEmail) {
            return ['normalized' => 0, 'skipped' => 0];
        }

        if ($this->hasConflict($customer, $canonicalEmail)) {
            $io->warning(sprintf(
                'Skipped customer "%s": "%s" already belongs to another customer.',
                $customer->getUlid(),
                $canonicalEmail
            ));

            return ['normalized' => 0, 'skipped' => 1];
        }

        $customer->setEmail($canonicalEmail);
        $this->customerRepository->save($customer);

        return ['normalized' => 1, 'skipped' => 0];
    }

    private function hasConflict(
        Customer $customer,
        string $canonicalEmail
    ): bool {
        $existing = $this->customerRepository->findByEmail($canonicalEmail);

        return $existing instanceof Customer
            && $existing->getUlid() !== $customer->getUlid();
    }
}
