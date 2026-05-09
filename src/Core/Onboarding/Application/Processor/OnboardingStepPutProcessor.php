<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Onboarding\Application\DTO\OnboardingStepPut;
use App\Core\Onboarding\Domain\Entity\OnboardingStep;
use App\Core\Onboarding\Domain\Exception\OnboardingStepNotFoundException;
use App\Core\Onboarding\Domain\Repository\OnboardingStepRepositoryInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<OnboardingStepPut, OnboardingStep>
 *
 * @psalm-suppress UnusedClass Wired by API Platform resource metadata.
 */
final readonly class OnboardingStepPutProcessor implements ProcessorInterface
{
    public function __construct(
        private OnboardingStepRepositoryInterface $repository,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param OnboardingStepPut $data
     * @param array<string, string> $uriVariables
     * @param array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): OnboardingStep {
        $ulid = $uriVariables['ulid'];
        $step = $this->repository->find($this->ulidFactory->create($ulid));

        if (! $step instanceof OnboardingStep) {
            throw OnboardingStepNotFoundException::withIri(sprintf('/api/onboarding_steps/%s', $ulid));
        }

        $step->update(
            (string) $data->code,
            (string) $data->label,
            (int) $data->position,
            (bool) $data->enabled
        );

        $this->repository->save($step);

        return $step;
    }
}
