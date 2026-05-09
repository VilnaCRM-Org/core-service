<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\ValueObject;

final readonly class TariffPlanPrice
{
    public function __construct(
        private int $cents,
        private string $currency,
        private string $period,
    ) {
    }

    public function getCents(): int
    {
        return $this->cents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPeriod(): string
    {
        return $this->period;
    }
}
