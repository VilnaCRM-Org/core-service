<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\DTO;

final class TariffPlanPut
{
    /**
     * @var list<string>
     */
    public iterable $deploymentOptions;

    public string $code;

    public string $name;

    public string $description;

    public bool $functionalLimitations;

    public ?int $userLimit = null;

    public int $priceCents;

    public string $priceCurrency;

    public string $pricePeriod;

    public int $position;

    public bool $enabled;
}
