<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\DTO;

final class TariffPlanCreate
{
    /**
     * @var list<string>|null
     */
    public ?array $deploymentOptions = null;

    public ?string $code = null;

    public ?string $name = null;

    public ?string $description = null;

    public ?bool $functionalLimitations = null;

    public ?int $userLimit = null;

    public ?int $priceCents = null;

    public ?string $priceCurrency = null;

    public ?string $pricePeriod = null;

    public ?int $position = null;

    public ?bool $enabled = null;
}
