<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Onboarding\Application\DTO\TariffPlanPut;
use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Core\Onboarding\Domain\Exception\TariffPlanNotFoundException;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanPrice;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<TariffPlanPut, TariffPlan>
 *
 * @psalm-suppress UnusedClass Wired by API Platform resource metadata.
 */
final readonly class TariffPlanPutProcessor implements ProcessorInterface
{
    public function __construct(
        private TariffPlanRepositoryInterface $repository,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param TariffPlanPut $data
     * @param array<string, string> $uriVariables
     * @param array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): TariffPlan {
        $ulid = $uriVariables['ulid'];
        $plan = $this->repository->find($this->ulidFactory->create($ulid));

        if (! $plan instanceof TariffPlan) {
            throw TariffPlanNotFoundException::withIri(sprintf('/api/tariff_plans/%s', $ulid));
        }

        $plan->update($this->createDetails($data));

        $this->repository->save($plan);

        return $plan;
    }

    private function createDetails(TariffPlanPut $data): TariffPlanDetails
    {
        return new TariffPlanDetails(
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
        );
    }
}
