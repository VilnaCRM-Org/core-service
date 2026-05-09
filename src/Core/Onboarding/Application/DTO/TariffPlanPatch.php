<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\DTO;

final class TariffPlanPatch
{
    /**
     * @var list<string>|null
     */
    public ?iterable $deploymentOptions = null;

    public ?string $code = null;

    public ?string $name = null;

    public ?string $description = null;

    public ?bool $functionalLimitations = null;

    public ?int $userLimit;

    public ?int $priceCents = null;

    public ?string $priceCurrency = null;

    public ?string $pricePeriod = null;

    public ?int $position = null;

    public ?bool $enabled = null;
}
