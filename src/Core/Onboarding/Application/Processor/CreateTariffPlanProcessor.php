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
     * @param array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        $uriVariables = [],
        $context = []
    ): TariffPlan {
        $plan = $this->planFactory->create(
            $this->detailsFactory->create(
                $data->code,
                $data->name,
                $data->description,
                $data->deploymentOptions,
                $data->functionalLimitations,
                $data->userLimit,
                $data->priceCents,
                $data->priceCurrency,
                $data->pricePeriod,
                $data->position,
                $data->enabled
            ),
            $this->ulidTransformer->transformFromSymfonyUlid(
                $this->symfonyUlidFactory->create()
            )
        );

        $this->repository->save($plan);

        return $plan;
    }
}
