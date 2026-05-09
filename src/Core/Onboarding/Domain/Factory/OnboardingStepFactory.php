<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Factory;

use App\Core\Onboarding\Domain\Entity\OnboardingStep;
use App\Shared\Domain\ValueObject\UlidInterface;

final class OnboardingStepFactory
{
    public function create(
        string $code,
        string $label,
        int $position,
        bool $enabled,
        UlidInterface $ulid
    ): OnboardingStep {
        return new OnboardingStep($code, $label, $position, $enabled, $ulid);
    }
}
