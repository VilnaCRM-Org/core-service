<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\ValueObject;

use InvalidArgumentException;

final readonly class TariffPlanPrice
{
    private int $cents;

    private string $currency;

    private string $period;

    public function __construct(int $cents, string $currency, string $period)
    {
        $currency = trim($currency);
        $period = trim($period);

        if ($cents < 0) {
            throw new InvalidArgumentException('Tariff plan price cents must be zero or greater.');
        }

        if ($currency === '') {
            throw new InvalidArgumentException('Tariff plan price currency must not be empty.');
        }

        if ($period === '') {
            throw new InvalidArgumentException('Tariff plan price period must not be empty.');
        }

        $this->cents = $cents;
        $this->currency = $currency;
        $this->period = $period;
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
