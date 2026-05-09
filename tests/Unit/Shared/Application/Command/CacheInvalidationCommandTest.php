<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use App\Tests\Unit\UnitTestCase;

final class CacheInvalidationCommandTest extends UnitTestCase
{
    public function testCreateKeepsPayloadTagsRefreshCommandsAndBuildsDedupeKey(): void
    {
        $context = 'customer';
        $source = 'domain_event';
        $operation = 'updated';
        $tagOne = 'cache.customer';
        $tagTwo = 'cache.customer.1';
        $tags = CacheInvalidationTagSet::create($tagOne, $tagTwo, $tagOne);
        $refreshCommand = $this->refreshCommand($context, $source);

        $command = CacheInvalidationCommand::create(
            $context,
            $source,
            $operation,
            $tags,
            CacheRefreshCommandCollection::create($refreshCommand)
        );

        $this->assertCommandPayload($command, $context, $source, $operation);
        self::assertSame([$tagOne, $tagTwo], iterator_to_array($command->tags()));
        self::assertSame([$refreshCommand], iterator_to_array($command->refreshCommands()));
        $this->assertDedupeKey($command, $context, $source, $operation, $tagOne, $tagTwo);
    }

    public function testCreateSupportsEmptyTags(): void
    {
        $context = $this->faker->word();
        $source = $this->faker->word();
        $operation = $this->faker->word();
        $tags = CacheInvalidationTagSet::create();

        $command = CacheInvalidationCommand::create(
            $context,
            $source,
            $operation,
            $tags,
            CacheRefreshCommandCollection::create()
        );

        self::assertTrue($command->tags()->isEmpty());
        self::assertCount(0, $command->tags());
        $this->assertDedupeKey($command, $context, $source, $operation);
    }

    public function testDedupeKeyIsIndependentFromTagOrder(): void
    {
        $first = CacheInvalidationCommand::create(
            'customer',
            'domain_event',
            'updated',
            CacheInvalidationTagSet::create('customer.2', 'customer.1'),
            CacheRefreshCommandCollection::create()
        );
        $second = CacheInvalidationCommand::create(
            'customer',
            'domain_event',
            'updated',
            CacheInvalidationTagSet::create('customer.1', 'customer.2'),
            CacheRefreshCommandCollection::create()
        );

        self::assertSame($first->dedupeKey(), $second->dedupeKey());
    }

    private function refreshCommand(string $context, string $source): CacheRefreshCommand
    {
        return CacheRefreshCommand::create(
            $context,
            $this->faker->word(),
            $this->faker->word(),
            (string) $this->faker->ulid(),
            CacheRefreshPolicy::SOURCE_EVENT_SNAPSHOT,
            $source,
            (string) $this->faker->ulid(),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );
    }

    private function dedupeKey(
        string $context,
        string $source,
        string $operation,
        string ...$tags
    ): string {
        sort($tags, \SORT_STRING);

        return hash('sha256', json_encode([
            'context' => $context,
            'source' => $source,
            'operation' => $operation,
            'tags' => $tags,
        ], \JSON_THROW_ON_ERROR));
    }

    private function assertCommandPayload(
        CacheInvalidationCommand $command,
        string $context,
        string $source,
        string $operation
    ): void {
        self::assertSame($context, $command->context());
        self::assertSame($source, $command->source());
        self::assertSame($operation, $command->operation());
    }

    private function assertDedupeKey(
        CacheInvalidationCommand $command,
        string $context,
        string $source,
        string $operation,
        string ...$tags
    ): void {
        self::assertSame(
            $this->dedupeKey($context, $source, $operation, ...$tags),
            $command->dedupeKey()
        );
    }
}
