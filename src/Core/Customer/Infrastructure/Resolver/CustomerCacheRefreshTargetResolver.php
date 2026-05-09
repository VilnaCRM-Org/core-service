<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Resolver;

use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Shared\Application\DTO\CacheRefreshTarget;
use App\Shared\Application\Resolver\CacheRefreshTargetResolverInterface;
use InvalidArgumentException;

final readonly class CustomerCacheRefreshTargetResolver implements
    CacheRefreshTargetResolverInterface
{
    public function supports(string $context, string $family): bool
    {
        return $context === CustomerCachePolicyCollection::CONTEXT
            && in_array($family, [
                CustomerCachePolicyCollection::FAMILY_DETAIL,
                CustomerCachePolicyCollection::FAMILY_LOOKUP,
            ], true);
    }

    public function resolve(
        string $context,
        string $family,
        string $identifierName,
        string $identifierValue
    ): CacheRefreshTarget {
        if (! $this->supports($context, $family) || $identifierValue === '') {
            throw $this->unsupportedTarget($context, $family, $identifierName);
        }

        return match ([$family, $identifierName]) {
            [CustomerCachePolicyCollection::FAMILY_DETAIL, 'customer_id'],
            [CustomerCachePolicyCollection::FAMILY_LOOKUP, 'email'] => CacheRefreshTarget::create(
                $context,
                $family,
                $identifierName,
                $identifierValue
            ),
            default => throw $this->unsupportedTarget($context, $family, $identifierName),
        };
    }

    /**
     * @return list<array{context: string, family: string, identifiers: array<string, string>}>
     */
    public function resolveForCreatedEvent(CustomerCreatedEvent $event): array
    {
        $targets = [$this->detail($event->customerId())];

        if ($event->customerEmail() !== '') {
            $targets[] = $this->lookup($event->customerEmail());
        }

        return $targets;
    }

    /**
     * @return list<array{context: string, family: string, identifiers: array<string, string>}>
     */
    public function resolveForUpdatedEvent(CustomerUpdatedEvent $event): array
    {
        $targets = [$this->detail($event->customerId())];

        if ($event->currentEmail() !== '') {
            $targets[] = $this->lookup($event->currentEmail());
        }

        if (
            $event->emailChanged()
            && $event->previousEmail() !== null
            && $event->previousEmail() !== ''
        ) {
            $targets[] = $this->lookup($event->previousEmail());
        }

        return $targets;
    }

    /**
     * @return list<array{context: string, family: string, identifiers: array<string, string>}>
     */
    public function resolveForDeletedEvent(CustomerDeletedEvent $event): array
    {
        $targets = [$this->detail($event->customerId())];

        if ($event->customerEmail() !== '') {
            $targets[] = $this->lookup($event->customerEmail());
        }

        return $targets;
    }

    /**
     * @return array{context: string, family: string, identifiers: array<string, string>}
     */
    public function detail(string $customerId): array
    {
        return [
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_DETAIL,
            'identifiers' => ['customer_id' => $customerId],
        ];
    }

    /**
     * @return array{context: string, family: string, identifiers: array<string, string>}
     */
    public function lookup(string $email): array
    {
        return [
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'identifiers' => ['email' => $email],
        ];
    }

    private function unsupportedTarget(
        string $context,
        string $family,
        string $identifierName
    ): InvalidArgumentException {
        return new InvalidArgumentException(sprintf(
            'Unsupported cache refresh target "%s.%s" for identifier "%s".',
            $context,
            $family,
            $identifierName
        ));
    }
}
