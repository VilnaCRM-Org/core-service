<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Onboarding\Application\DTO\TariffPlanCreate;
use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanPrice;
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
        array $uriVariables = [],
        array $context = []
    ): TariffPlan {
        $plan = new TariffPlan(
            new TariffPlanDetails(
                (string) $data->code,
                (string) $data->name,
                (string) $data->description,
                $data->deploymentOptions ?? [],
                (bool) $data->functionalLimitations,
                $data->userLimit,
                new TariffPlanPrice(
                    (int) $data->priceCents,
                    (string) $data->priceCurrency,
                    (string) $data->pricePeriod
                ),
                (int) $data->position,
                (bool) $data->enabled
            ),
            $this->ulidTransformer->transformFromSymfonyUlid(
                $this->symfonyUlidFactory->create()
            )
        );

        $this->repository->save($plan);

        return $plan;
    }
}
