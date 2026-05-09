<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Factory;

use App\Core\Onboarding\Domain\Entity\TariffPlan;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Shared\Domain\ValueObject\UlidInterface;

final class TariffPlanFactory
{
    public function create(TariffPlanDetails $details, UlidInterface $ulid): TariffPlan
    {
        return new TariffPlan($details, $ulid);
    }
}
