<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Tests\Unit\UnitTestCase;

final class CacheRefreshCommandTest extends UnitTestCase
{
    public function testCreateKeepsScalarPayloadAndBuildsStableDedupeKey(): void
    {
        $command = $this->repositoryRefreshCommand();

        self::assertSame('customer', $command->context());
        self::assertSame('detail', $command->family());
        self::assertSame('customer_id', $command->identifierName());
        self::assertSame('customer-1', $command->identifierValue());
        self::assertSame(CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH, $command->refreshSource());
        self::assertSame('domain_event', $command->sourceName());
        self::assertSame('source-1', $command->sourceId());
        self::assertSame('2026-01-01T00:00:00+00:00', $command->occurredOn());
        self::assertSame($this->dedupeKey($command), $command->dedupeKey());
    }

    public function testCreateKeepsInvalidateOnlyRefreshSource(): void
    {
        $command = CacheRefreshCommand::create(
            $this->faker->word(),
            $this->faker->word(),
            $this->faker->word(),
            (string) $this->faker->ulid(),
            CacheRefreshPolicy::SOURCE_INVALIDATE_ONLY,
            $this->faker->word(),
            (string) $this->faker->ulid(),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );

        self::assertSame(CacheRefreshPolicy::SOURCE_INVALIDATE_ONLY, $command->refreshSource());
    }

    public function testDedupeKeyCollapsesEquivalentTargetsAcrossSources(): void
    {
        $domainEventCommand = CacheRefreshCommand::create(
            'customer',
            'detail',
            'customer_id',
            'customer-1',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH,
            'domain_event',
            'domain-event-1',
            '2026-01-01T00:00:00+00:00'
        );
        $odmCommand = CacheRefreshCommand::create(
            'customer',
            'detail',
            'customer_id',
            'customer-1',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH,
            'odm_change_set',
            'odm-change-1',
            '2026-01-01T00:00:01+00:00'
        );

        self::assertSame($domainEventCommand->dedupeKey(), $odmCommand->dedupeKey());
    }

    public function testDedupeKeyDefaultsToCaseInsensitiveIdentifier(): void
    {
        $lowercaseCommand = $this->repositoryRefreshCommand('email', 'case@example.com');
        $mixedCaseCommand = $this->repositoryRefreshCommand('email', 'Case@Example.COM');

        self::assertSame($lowercaseCommand->dedupeKey(), $mixedCaseCommand->dedupeKey());
    }

    public function testDedupeKeySupportsCaseSensitiveIdentifier(): void
    {
        $lowercaseCommand = $this->repositoryRefreshCommand(
            'case_sensitive_external_id',
            'abc',
            false
        );
        $mixedCaseCommand = $this->repositoryRefreshCommand(
            'case_sensitive_external_id',
            'ABC',
            false
        );

        self::assertNotSame($lowercaseCommand->dedupeKey(), $mixedCaseCommand->dedupeKey());
        self::assertSame($this->dedupeKey($lowercaseCommand, false), $lowercaseCommand->dedupeKey());
        self::assertSame($this->dedupeKey($mixedCaseCommand, false), $mixedCaseCommand->dedupeKey());
    }

    private function repositoryRefreshCommand(
        string $identifierName = 'customer_id',
        string $identifierValue = 'customer-1',
        bool $caseInsensitiveIdentifier = true
    ): CacheRefreshCommand {
        return CacheRefreshCommand::create(
            'customer',
            'detail',
            $identifierName,
            $identifierValue,
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH,
            'domain_event',
            'source-1',
            '2026-01-01T00:00:00+00:00',
            $caseInsensitiveIdentifier
        );
    }

    private function dedupeKey(
        CacheRefreshCommand $command,
        bool $caseInsensitiveIdentifier = true
    ): string {
        $identifierValue = $command->identifierValue();
        if ($caseInsensitiveIdentifier) {
            $identifierValue = strtolower($identifierValue);
        }

        return $this->dedupeKeyForIdentifier($command, $identifierValue);
    }

    private function dedupeKeyForIdentifier(
        CacheRefreshCommand $command,
        string $identifierValue
    ): string {
        return hash('sha256', json_encode([
            'context' => $command->context(),
            'family' => $command->family(),
            'identifier_name' => $command->identifierName(),
            'identifier_value' => $identifierValue,
            'refresh_source' => $command->refreshSource(),
        ], \JSON_THROW_ON_ERROR));
    }
}
