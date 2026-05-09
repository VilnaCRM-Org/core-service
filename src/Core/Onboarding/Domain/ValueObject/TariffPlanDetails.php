<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\ValueObject;

final readonly class TariffPlanDetails
{
    /**
     * @var list<string>
     */
    private iterable $deploymentOptions;

    /**
     * @param list<string> $deploymentOptions
     */
    public function __construct(
        private string $code,
        private string $name,
        private string $description,
        iterable $deploymentOptions,
        private bool $functionalLimitations,
        private ?int $userLimit,
        private TariffPlanPrice $price,
        private int $position,
        private bool $enabled,
    ) {
        $this->deploymentOptions = $deploymentOptions;
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

    public function getPrice(): TariffPlanPrice
    {
        return $this->price;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
