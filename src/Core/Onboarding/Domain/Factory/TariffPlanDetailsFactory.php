<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Factory;

use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Core\Onboarding\Domain\ValueObject\TariffPlanPrice;

final class TariffPlanDetailsFactory
{
    /**
     * @param list<string> $deploymentOptions
     * @param array{cents: int, currency: string, period: string} $priceData
     */
    public function create(
        string $code,
        string $name,
        string $description,
        iterable $deploymentOptions,
        bool $functionalLimitations,
        ?int $userLimit,
        $priceData,
        int $position,
        bool $enabled
    ): TariffPlanDetails {
        return new TariffPlanDetails(
            $code,
            $name,
            $description,
            $deploymentOptions,
            $functionalLimitations,
            $userLimit,
            new TariffPlanPrice(
                $priceData['cents'],
                $priceData['currency'],
                $priceData['period']
            ),
            $position,
            $enabled
        );
    }
}
