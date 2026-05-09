<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Repository;

use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Shared\Domain\ValueObject\UlidInterface;

interface TariffPlanRepositoryInterface
{
    public function save(TariffPlan $plan, bool $flush = true): void;

    public function flush(): void;

    public function findByUlid(UlidInterface $ulid): ?TariffPlan;

    public function findOneByCode(string $code): ?TariffPlan;
}
