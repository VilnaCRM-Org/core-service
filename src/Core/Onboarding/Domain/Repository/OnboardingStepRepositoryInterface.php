<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Repository;

use App\Core\Onboarding\Domain\Entity\OnboardingStep;
use App\Shared\Domain\ValueObject\UlidInterface;

interface OnboardingStepRepositoryInterface
{
    public function save(OnboardingStep $step, bool $flush = true): void;

    public function flush(): void;

    public function findByUlid(UlidInterface $ulid): ?OnboardingStep;

    public function findOneByCode(string $code): ?OnboardingStep;
}
