<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Command;

use App\Core\Onboarding\Domain\Factory\OnboardingStepFactory;
use App\Core\Onboarding\Domain\Factory\TariffPlanDetailsFactory;
use App\Core\Onboarding\Domain\Factory\TariffPlanFactory;
use App\Core\Onboarding\Domain\Repository\OnboardingStepRepositoryInterface;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Factory\UlidFactory as SymfonyUlidFactory;

/**
 * @psalm-suppress UnusedClass Wired as a Symfony console command.
 */
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
        private readonly OnboardingStepFactory $stepFactory,
        private readonly TariffPlanDetailsFactory $planDetailsFactory,
        private readonly TariffPlanFactory $planFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:onboarding:seed-defaults')
            ->setDescription('Seed default onboarding steps and tariff plans.');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        foreach (self::STEPS as [$code, $label, $position]) {
            $this->upsertStep($code, $label, $position);
        }

        foreach (self::PLANS as $plan) {
            $this->upsertPlan($plan);
        }

        $this->planRepository->flush();

        $output->writeln('<info>Default onboarding data has been seeded.</info>');

        return Command::SUCCESS;
    }

    private function upsertStep(
        string $code,
        string $label,
        int $position
    ): void {
        $step = $this->stepRepository->findOneByCode($code);

        if ($step === null) {
            $step = $this->stepFactory->create(
                $code,
                $label,
                $position,
                true,
                $this->ulidTransformer->transformFromSymfonyUlid(
                    $this->symfonyUlidFactory->create()
                )
            );
        } else {
            $step->update($code, $label, $position, true);
        }

        $this->stepRepository->save($step, false);
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
    private function upsertPlan($planData): void
    {
        $details = $this->createDetails($planData);
        $plan = $this->planRepository->findOneByCode($planData['code']);

        if ($plan === null) {
            $plan = $this->planFactory->create(
                $details,
                $this->ulidTransformer->transformFromSymfonyUlid(
                    $this->symfonyUlidFactory->create()
                )
            );
        } else {
            $plan->update($details);
        }

        $this->planRepository->save($plan, false);
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
    private function createDetails($planData): TariffPlanDetails
    {
        return $this->planDetailsFactory->create(
            $planData['code'],
            $planData['name'],
            $planData['description'],
            $planData['deploymentOptions'],
            $planData['functionalLimitations'],
            $planData['userLimit'],
            $planData['priceCents'],
            $planData['priceCurrency'],
            $planData['pricePeriod'],
            $planData['position'],
            true
        );
    }
}
