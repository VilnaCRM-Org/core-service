<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Repository;

use App\Core\Onboarding\Domain\Entity\OnboardingStep;

interface OnboardingStepRepositoryInterface
{
    public function save(OnboardingStep $step): void;

    public function find(mixed $id, int $lockMode = 0, ?int $lockVersion = null): ?object;

    public function findOneByCode(string $code): ?OnboardingStep;
}
