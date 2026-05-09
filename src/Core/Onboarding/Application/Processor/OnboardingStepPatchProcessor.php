<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Onboarding\Application\DTO\OnboardingStepPatch;
use App\Core\Onboarding\Domain\Entity\OnboardingStep;
use App\Core\Onboarding\Domain\Exception\OnboardingStepNotFoundException;
use App\Core\Onboarding\Domain\Repository\OnboardingStepRepositoryInterface;
use App\Shared\Application\Extractor\PatchUlidExtractor;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<OnboardingStepPatch, OnboardingStep>
 *
 * @psalm-suppress UnusedClass Wired by API Platform resource metadata.
 */
final readonly class OnboardingStepPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private OnboardingStepRepositoryInterface $repository,
        private PatchUlidExtractor $patchUlidExtractor,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param OnboardingStepPatch $data
     * @param array<string, string> $uriVariables
     * @param array<string, array<array-key, object|scalar|null>|object|scalar|null> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        $uriVariables = [],
        $context = []
    ): OnboardingStep {
        $ulid = $this->patchUlidExtractor->extract(
            $uriVariables,
            $data->id,
            static fn () => OnboardingStepNotFoundException::withIri(
                '/api/onboarding_steps/unknown'
            )
        );
        $step = $this->findStep($ulid);

        $step->update(
            $data->code ?? $step->getCode(),
            $data->label ?? $step->getLabel(),
            $data->position ?? $step->getPosition(),
            $data->enabled ?? $step->isEnabled()
        );

        $this->repository->save($step);

        return $step;
    }

    private function findStep(string $ulid): OnboardingStep
    {
        $step = $this->repository->findByUlid($this->ulidFactory->create($ulid));

        return $step ?? throw OnboardingStepNotFoundException::withIri(
            sprintf('/api/onboarding_steps/%s', $ulid)
        );
    }
}
