<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Onboarding\Application\DTO\TariffPlanCreate;
use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Core\Onboarding\Domain\Factory\TariffPlanDetailsFactory;
use App\Core\Onboarding\Domain\Factory\TariffPlanFactory;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory as SymfonyUlidFactory;

/**
 * @implements ProcessorInterface<TariffPlanCreate, TariffPlan>
 *
 * @psalm-suppress UnusedClass Wired by API Platform resource metadata.
 */
final readonly class CreateTariffPlanProcessor implements ProcessorInterface
{
    public function __construct(
        private TariffPlanRepositoryInterface $repository,
        private SymfonyUlidFactory $symfonyUlidFactory,
        private UlidTransformer $ulidTransformer,
        private TariffPlanDetailsFactory $detailsFactory,
        private TariffPlanFactory $planFactory,
    ) {
    }

    /**
     * @param TariffPlanCreate $data
     * @param array<string, string> $uriVariables
     * @param array<string, array<array-key, object|scalar|null>|object|scalar|null> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        $uriVariables = [],
        $context = []
    ): TariffPlan {
        $plan = $this->planFactory->create(
            $this->createDetails($data),
            $this->ulidTransformer->transformFromSymfonyUlid(
                $this->symfonyUlidFactory->create()
            )
        );

        $this->repository->save($plan);

        return $plan;
    }

    private function createDetails(TariffPlanCreate $data): TariffPlanDetails
    {
        return $this->detailsFactory->create(
            $data->code,
            $data->name,
            $data->description,
            $data->deploymentOptions,
            $data->functionalLimitations,
            $data->userLimit,
            [
                'cents' => $data->priceCents,
                'currency' => $data->priceCurrency,
                'period' => $data->pricePeriod,
            ],
            $data->position,
            $data->enabled
        );
    }
}
