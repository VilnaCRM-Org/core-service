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
        $uriVariables = [],
        $context = []
    ): OnboardingStep {
        $ulid = $this->ulidFromUriVariables($uriVariables);
        $step = $this->repository->findByUlid($this->ulidFactory->create($ulid));

        if ($step === null) {
            throw OnboardingStepNotFoundException::withIri(sprintf('/api/onboarding_steps/%s', $ulid));
        }

        $step->update(
            $data->code,
            $data->label,
            $data->position,
            $data->enabled
        );

        $this->repository->save($step);

        return $step;
    }

    /**
     * @param array<string, string> $uriVariables
     */
    private function ulidFromUriVariables($uriVariables): string
    {
        $ulid = $uriVariables['ulid'] ?? null;

        return $ulid !== null && $ulid !== ''
            ? $ulid
            : throw OnboardingStepNotFoundException::withIri('/api/onboarding_steps/unknown');
    }
}
