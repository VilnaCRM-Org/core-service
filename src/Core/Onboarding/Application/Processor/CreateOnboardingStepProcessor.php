<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Onboarding\Application\DTO\OnboardingStepCreate;
use App\Core\Onboarding\Domain\Entity\OnboardingStep;
use App\Core\Onboarding\Domain\Repository\OnboardingStepRepositoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory as SymfonyUlidFactory;

/**
 * @implements ProcessorInterface<OnboardingStepCreate, OnboardingStep>
 *
 * @psalm-suppress UnusedClass Wired by API Platform resource metadata.
 */
final readonly class CreateOnboardingStepProcessor implements ProcessorInterface
{
    public function __construct(
        private OnboardingStepRepositoryInterface $repository,
        private SymfonyUlidFactory $symfonyUlidFactory,
        private UlidTransformer $ulidTransformer,
    ) {
    }

    /**
     * @param OnboardingStepCreate $data
     * @param array<string, string> $uriVariables
     * @param array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): OnboardingStep {
        $step = new OnboardingStep(
            (string) $data->code,
            (string) $data->label,
            (int) $data->position,
            (bool) $data->enabled,
            $this->ulidTransformer->transformFromSymfonyUlid(
                $this->symfonyUlidFactory->create()
            )
        );

        $this->repository->save($step);

        return $step;
    }
}
