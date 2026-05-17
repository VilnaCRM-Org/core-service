<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final readonly class CacheInvalidationRule
{
    public const OPERATION_CREATED = 'created';
    public const OPERATION_UPDATED = 'updated';
    public const OPERATION_DELETED = 'deleted';

    /**
     * @param class-string $subject
     */
    public function __construct(
        private string $context,
        private string $source,
        private string $subject,
        private string $operation,
        private string $refreshSource
    ) {
    }

    public function context(): string
    {
        return $this->context;
    }

    public function source(): string
    {
        return $this->source;
    }

    /**
     * @return class-string
     */
    public function subject(): string
    {
        return $this->subject;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function refreshSource(): string
    {
        return $this->refreshSource;
    }
}
