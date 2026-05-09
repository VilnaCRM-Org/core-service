<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Entity;

use App\Shared\Domain\ValueObject\UlidInterface;

final class OnboardingStep
{
    public function __construct(
        private string $code,
        private string $label,
        private int $position,
        private bool $enabled,
        private UlidInterface $ulid,
    ) {
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function update(
        string $code,
        string $label,
        int $position,
        bool $enabled
    ): void {
        $this->code = $code;
        $this->label = $label;
        $this->position = $position;
        $this->enabled = $enabled;
    }
}
