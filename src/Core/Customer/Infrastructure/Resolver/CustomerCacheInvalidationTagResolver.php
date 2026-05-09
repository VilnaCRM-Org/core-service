<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Resolver;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Collection\CustomerCacheInvalidationRuleCollection;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Collection\CustomerCacheTagCollection;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheFieldChange;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\Resolver\DocumentCacheInvalidationResolverInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * @phpstan-type CustomerIdentifierMap array{
 *     customer_id?: string|null,
 *     email?: string|null
 * }
 * @phpstan-type CustomerEmailChangeSet array{
 *     email?: array{0?: string|null, 1?: string|null}
 * }
 * @phpstan-type CustomerRefreshMetadata array{
 *     refresh_source: string,
 *     source_id: string,
 *     occurred_on: string
 * }
 */
final readonly class CustomerCacheInvalidationTagResolver implements
    DocumentCacheInvalidationResolverInterface
{
    private CustomerCacheInvalidationRuleCollection $rules;

    public function __construct(
        private CacheKeyBuilder $cacheKeyBuilder,
        ?CustomerCacheInvalidationRuleCollection $rules = null
    ) {
        $this->rules = $rules ?? new CustomerCacheInvalidationRuleCollection();
    }

    public function supports(object $document, string $operation): bool
    {
        return $this->rules->forDocumentChange($document::class, $operation) !== [];
    }

    public function context(object $document, string $operation): string
    {
        return CustomerCachePolicyCollection::CONTEXT;
    }

    public function resolveTags(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheInvalidationTagSet {
        if (! $document instanceof Customer) {
            return CacheInvalidationTagSet::create(
                'customer',
                'customer.collection',
                'customer.reference'
            );
        }

        $tags = $this->resolveForCustomerIdentifiers(
            customerId: (string) $document->getUlid(),
            currentEmail: $this->emailFromChangeSetOrDocument($document, $changeSet),
            previousEmail: $this->previousEmailFromChangeSet($changeSet)
        );

        return CacheInvalidationTagSet::create(...iterator_to_array($tags));
    }

    public function resolveRefreshCommands(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheRefreshCommandCollection {
        if (! $document instanceof Customer) {
            return CacheRefreshCommandCollection::create();
        }

        return $this->resolveCustomerRefreshCommands(
            $document,
            $operation,
            $changeSet
        );
    }

    /**
     * @param CustomerIdentifierMap $identifiers
     * @param CustomerEmailChangeSet $changeSet
     */
    public function resolveForChangeSet(
        array $identifiers,
        array $changeSet = []
    ): CustomerCacheTagCollection {
        return $this->resolveForCustomerIdentifiers(
            customerId: $identifiers['customer_id'] ?? null,
            currentEmail: $this->resolveCurrentEmail($identifiers, $changeSet),
            previousEmail: $this->resolvePreviousEmail($changeSet)
        );
    }

    public function resolveForCustomerIdentifiers(
        ?string $customerId,
        ?string $currentEmail,
        ?string $previousEmail = null
    ): CustomerCacheTagCollection {
        return $this->withPreviousEmailTag(
            $this->withCurrentEmailTag(
                $this->withCustomerIdTag(new CustomerCacheTagCollection(), $customerId),
                $currentEmail
            ),
            $previousEmail,
            $currentEmail
        )->with('customer.collection');
    }

    private function emailTag(string $email): string
    {
        return 'customer.email.' . $this->cacheKeyBuilder->hashEmail($email);
    }

    /**
     * @param CustomerIdentifierMap $identifiers
     * @param CustomerEmailChangeSet $changeSet
     */
    private function resolveCurrentEmail(array $identifiers, array $changeSet): ?string
    {
        if (isset($changeSet['email'][1]) && is_string($changeSet['email'][1])) {
            return $changeSet['email'][1];
        }

        return $identifiers['email'] ?? null;
    }

    /**
     * @param CustomerEmailChangeSet $changeSet
     */
    private function resolvePreviousEmail(array $changeSet): ?string
    {
        if (isset($changeSet['email'][0]) && is_string($changeSet['email'][0])) {
            return $changeSet['email'][0];
        }

        return null;
    }

    private function emailFromChangeSetOrDocument(
        Customer $customer,
        CacheChangeSet $changeSet
    ): string {
        $emailChange = $changeSet->get('email');

        if (
            $emailChange instanceof CacheFieldChange
            && is_string($emailChange->newValue())
        ) {
            return $emailChange->newValue();
        }

        return $customer->getEmail();
    }

    private function previousEmailFromChangeSet(CacheChangeSet $changeSet): ?string
    {
        $emailChange = $changeSet->get('email');

        if (
            $emailChange instanceof CacheFieldChange
            && is_string($emailChange->oldValue())
        ) {
            return $emailChange->oldValue();
        }

        return null;
    }

    private function resolveCustomerRefreshCommands(
        Customer $customer,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheRefreshCommandCollection {
        $customerId = $customer->getUlid();
        $currentEmail = $this->emailFromChangeSetOrDocument($customer, $changeSet);
        $metadata = $this->refreshMetadata($operation, $customerId, $currentEmail);
        $commands = [
            $this->detailRefreshCommand($customerId, $metadata),
        ];

        return CacheRefreshCommandCollection::create(
            ...$this->withLookupRefreshCommand(
                $commands,
                $currentEmail,
                $metadata
            )
        );
    }

    private function withCustomerIdTag(
        CustomerCacheTagCollection $tags,
        ?string $customerId
    ): CustomerCacheTagCollection {
        if ($customerId === null || $customerId === '') {
            return $tags;
        }

        return $tags->with('customer.' . $customerId);
    }

    private function withCurrentEmailTag(
        CustomerCacheTagCollection $tags,
        ?string $currentEmail
    ): CustomerCacheTagCollection {
        if ($currentEmail === null || $currentEmail === '') {
            return $tags;
        }

        return $tags->with($this->emailTag($currentEmail));
    }

    private function withPreviousEmailTag(
        CustomerCacheTagCollection $tags,
        ?string $previousEmail,
        ?string $currentEmail
    ): CustomerCacheTagCollection {
        if ($this->shouldAddPreviousEmailTag($previousEmail, $currentEmail)) {
            return $tags->with($this->emailTag((string) $previousEmail));
        }

        return $tags;
    }

    private function shouldAddPreviousEmailTag(
        ?string $previousEmail,
        ?string $currentEmail
    ): bool {
        if ($previousEmail === null || $previousEmail === '') {
            return false;
        }

        if ($currentEmail === null || $currentEmail === '') {
            return true;
        }

        return strtolower($previousEmail) !== strtolower($currentEmail);
    }

    private function refreshSourceForOperation(string $operation): string
    {
        if ($operation === CustomerCacheInvalidationRuleCollection::OPERATION_DELETED) {
            return CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY;
        }

        return CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY;
    }

    /**
     * @return CustomerRefreshMetadata
     */
    private function refreshMetadata(
        string $operation,
        string $customerId,
        string $currentEmail
    ): array {
        return [
            'refresh_source' => $this->refreshSourceForOperation($operation),
            'source_id' => $this->refreshSourceId(
                $operation,
                $customerId,
                $currentEmail
            ),
            'occurred_on' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ];
    }

    private function refreshSourceId(
        string $operation,
        string $customerId,
        string $currentEmail
    ): string {
        return hash('sha256', implode('|', [
            $operation,
            $customerId,
            $currentEmail,
        ]));
    }

    /**
     * @param CustomerRefreshMetadata $metadata
     */
    private function detailRefreshCommand(
        string $customerId,
        array $metadata
    ): CacheRefreshCommand {
        return CacheRefreshCommand::create(
            CustomerCachePolicyCollection::CONTEXT,
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            'customer_id',
            $customerId,
            $metadata['refresh_source'],
            'odm_change_set',
            $metadata['source_id'],
            $metadata['occurred_on']
        );
    }

    /**
     * @param list<CacheRefreshCommand> $commands
     * @param CustomerRefreshMetadata $metadata
     *
     * @return list<CacheRefreshCommand>
     */
    private function withLookupRefreshCommand(
        array $commands,
        string $currentEmail,
        array $metadata
    ): array {
        if ($currentEmail !== '') {
            $commands[] = CacheRefreshCommand::create(
                CustomerCachePolicyCollection::CONTEXT,
                CustomerCachePolicyCollection::FAMILY_LOOKUP,
                'email',
                $currentEmail,
                $metadata['refresh_source'],
                'odm_change_set',
                $metadata['source_id'],
                $metadata['occurred_on']
            );
        }

        return $commands;
    }
}
