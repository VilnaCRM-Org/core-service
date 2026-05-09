<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Onboarding\Application\DTO\TariffPlanPut;
use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Core\Onboarding\Domain\Exception\TariffPlanNotFoundException;
use App\Core\Onboarding\Domain\Factory\TariffPlanDetailsFactory;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
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
        private TariffPlanDetailsFactory $detailsFactory,
    ) {
    }

    /**
     * @param TariffPlanPut $data
     * @param array<string, string> $uriVariables
     * @param array<string, array<array-key, object|scalar|null>|object|scalar|null> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        $uriVariables = [],
        $context = []
    ): TariffPlan {
        $ulid = $this->ulidFromUriVariables($uriVariables);
        $plan = $this->repository->findByUlid($this->ulidFactory->create($ulid));

        if ($plan === null) {
            throw TariffPlanNotFoundException::withIri(sprintf('/api/tariff_plans/%s', $ulid));
        }

        $plan->update($this->createDetails($data));

        $this->repository->save($plan);

        return $plan;
    }

    private function createDetails(TariffPlanPut $data): TariffPlanDetails
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

    /**
     * @param array<string, string> $uriVariables
     */
    private function ulidFromUriVariables($uriVariables): string
    {
        $ulid = $uriVariables['ulid'] ?? null;

        return $ulid !== null && $ulid !== ''
            ? $ulid
            : throw TariffPlanNotFoundException::withIri('/api/tariff_plans/unknown');
    }
}
