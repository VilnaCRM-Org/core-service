<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Entity;

use App\Core\Onboarding\Domain\ValueObject\TariffPlanDetails;
use App\Shared\Domain\ValueObject\UlidInterface;

final class TariffPlan
{
    private string $code;

    private string $name;

    private string $description;

    /**
     * @var list<string>
     */
    private iterable $deploymentOptions;

    private bool $functionalLimitations;

    private ?int $userLimit;

    private int $priceCents;

    private string $priceCurrency;

    private string $pricePeriod;

    private int $position;

    private bool $enabled;

    public function __construct(
        TariffPlanDetails $details,
        private UlidInterface $ulid,
    ) {
        $this->applyDetails($details);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getUlid(): string
    {
        return (string) $this->ulid;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function getDeploymentOptions(): iterable
    {
        return $this->deploymentOptions;
    }

    public function hasFunctionalLimitations(): bool
    {
        return $this->functionalLimitations;
    }

    public function getUserLimit(): ?int
    {
        return $this->userLimit;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function getPriceCurrency(): string
    {
        return $this->priceCurrency;
    }

    public function getPricePeriod(): string
    {
        return $this->pricePeriod;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function update(TariffPlanDetails $details): void
    {
        $this->applyDetails($details);
    }

    private function applyDetails(TariffPlanDetails $details): void
    {
        $price = $details->getPrice();

        $this->code = $details->getCode();
        $this->name = $details->getName();
        $this->description = $details->getDescription();
        $this->deploymentOptions = $details->getDeploymentOptions();
        $this->functionalLimitations = $details->hasFunctionalLimitations();
        $this->userLimit = $details->getUserLimit();
        $this->priceCents = $price->getCents();
        $this->priceCurrency = $price->getCurrency();
        $this->pricePeriod = $price->getPeriod();
        $this->position = $details->getPosition();
        $this->enabled = $details->isEnabled();
    }
}
