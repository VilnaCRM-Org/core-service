<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Command;

use App\Core\Onboarding\Domain\Entity\OnboardingStep;
use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Core\Onboarding\Domain\Repository\OnboardingStepRepositoryInterface;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanPrice;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Factory\UlidFactory as SymfonyUlidFactory;

/**
 * @psalm-suppress UnusedClass Wired as a Symfony console command.
 */
#[AsCommand(
    name: 'app:onboarding:seed-defaults',
    description: 'Seed default onboarding steps and tariff plans.'
)]
final class SeedOnboardingDefaultsCommand extends Command
{
    private const STEPS = [
        ['tariff_plan', 'Tariff plan', 1],
        ['payment', 'Payment', 2],
        ['import_from_crm', 'Import from CRM', 3],
        ['project', 'Project', 4],
        ['product', 'Product', 5],
        ['kanban_docker', 'Kanban docker', 6],
        ['integration', 'Integration', 7],
        ['ip_telephony', 'IP telephony', 8],
        ['employees', 'Employees', 9],
    ];

    private const PLANS = [
        [
            'code' => 'free',
            'name' => 'Free rate',
            'description' => 'Cloud solution with no functional limitations for up to 50 users.',
            'deploymentOptions' => ['cloud'],
            'functionalLimitations' => false,
            'userLimit' => 50,
            'priceCents' => 0,
            'priceCurrency' => 'USD',
            'pricePeriod' => 'none',
            'position' => 1,
        ],
        [
            'code' => 'corporate',
            'name' => 'Corporate rate',
            'description' => 'Cloud and box solutions with no functional limitations and no user limit.',
            'deploymentOptions' => ['cloud', 'box'],
            'functionalLimitations' => false,
            'userLimit' => null,
            'priceCents' => 1000,
            'priceCurrency' => 'USD',
            'pricePeriod' => 'per_user',
            'position' => 2,
        ],
    ];

    public function __construct(
        private readonly OnboardingStepRepositoryInterface $stepRepository,
        private readonly TariffPlanRepositoryInterface $planRepository,
        private readonly SymfonyUlidFactory $symfonyUlidFactory,
        private readonly UlidTransformer $ulidTransformer,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        unset($input);

        foreach (self::STEPS as [$code, $label, $position]) {
            $this->upsertStep($code, $label, $position);
        }

        foreach (self::PLANS as $plan) {
            $this->upsertPlan($plan);
        }

        $output->writeln('<info>Default onboarding data has been seeded.</info>');

        return Command::SUCCESS;
    }

    private function upsertStep(
        string $code,
        string $label,
        int $position
    ): void {
        $step = $this->stepRepository->findOneByCode($code)
            ?? new OnboardingStep(
                $code,
                $label,
                $position,
                true,
                $this->ulidTransformer->transformFromSymfonyUlid(
                    $this->symfonyUlidFactory->create()
                )
            );

        $step->update($code, $label, $position, true);
        $this->stepRepository->save($step);
    }

    /**
     * @param array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     deploymentOptions: list<string>,
     *     functionalLimitations: bool,
     *     userLimit: int|null,
     *     priceCents: int,
     *     priceCurrency: string,
     *     pricePeriod: string,
     *     position: int
     * } $planData
     */
    private function upsertPlan(array $planData): void
    {
        $plan = $this->planRepository->findOneByCode($planData['code'])
            ?? new TariffPlan(
                $this->createDetails($planData),
                $this->ulidTransformer->transformFromSymfonyUlid(
                    $this->symfonyUlidFactory->create()
                )
            );

        $plan->update($this->createDetails($planData));
        $this->planRepository->save($plan);
    }

    /**
     * @param array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     deploymentOptions: list<string>,
     *     functionalLimitations: bool,
     *     userLimit: int|null,
     *     priceCents: int,
     *     priceCurrency: string,
     *     pricePeriod: string,
     *     position: int
     * } $planData
     */
    private function createDetails(array $planData): TariffPlanDetails
    {
        return new TariffPlanDetails(
            $planData['code'],
            $planData['name'],
            $planData['description'],
            $planData['deploymentOptions'],
            $planData['functionalLimitations'],
            $planData['userLimit'],
            new TariffPlanPrice(
                $planData['priceCents'],
                $planData['priceCurrency'],
                $planData['pricePeriod']
            ),
            $planData['position'],
            true
        );
    }
}
