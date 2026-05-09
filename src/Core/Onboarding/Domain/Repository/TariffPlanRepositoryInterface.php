<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Repository;

use App\Core\Onboarding\Domain\Entity\TariffPlan;

interface TariffPlanRepositoryInterface
{
    public function save(TariffPlan $plan): void;

    public function find(mixed $id, int $lockMode = 0, ?int $lockVersion = null): ?object;

    public function findOneByCode(string $code): ?TariffPlan;
}
