<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Onboarding\Application\DTO\TariffPlanPatch;
use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Core\Onboarding\Domain\Exception\TariffPlanNotFoundException;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanPrice;
use App\Shared\Application\Extractor\PatchUlidExtractor;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<TariffPlanPatch, TariffPlan>
 *
 * @psalm-suppress UnusedClass Wired by API Platform resource metadata.
 */
final readonly class TariffPlanPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private TariffPlanRepositoryInterface $repository,
        private PatchUlidExtractor $patchUlidExtractor,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param TariffPlanPatch $data
     * @param array<string, string> $uriVariables
     * @param array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): TariffPlan {
        $ulid = $this->patchUlidExtractor->extract(
            $uriVariables,
            $data->id,
            static fn () => TariffPlanNotFoundException::withIri('/api/tariff_plans/unknown')
        );
        $plan = $this->findPlan($ulid);

        $plan->update($this->createDetails($data, $plan));

        $this->repository->save($plan);

        return $plan;
    }

    private function createDetails(
        TariffPlanPatch $data,
        TariffPlan $plan
    ): TariffPlanDetails {
        return new TariffPlanDetails(
            $data->code ?? $plan->getCode(),
            $data->name ?? $plan->getName(),
            $data->description ?? $plan->getDescription(),
            $data->deploymentOptions ?? $plan->getDeploymentOptions(),
            $data->functionalLimitations ?? $plan->hasFunctionalLimitations(),
            $data->userLimit ?? $plan->getUserLimit(),
            new TariffPlanPrice(
                $data->priceCents ?? $plan->getPriceCents(),
                $data->priceCurrency ?? $plan->getPriceCurrency(),
                $data->pricePeriod ?? $plan->getPricePeriod()
            ),
            $data->position ?? $plan->getPosition(),
            $data->enabled ?? $plan->isEnabled()
        );
    }

    private function findPlan(string $ulid): TariffPlan
    {
        $plan = $this->repository->find($this->ulidFactory->create($ulid));

        return $plan instanceof TariffPlan
            ? $plan
            : throw TariffPlanNotFoundException::withIri(sprintf('/api/tariff_plans/%s', $ulid));
    }
}
