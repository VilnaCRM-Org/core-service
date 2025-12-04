<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\TagDescriptionProcessor;
use App\Tests\Unit\UnitTestCase;
use ReflectionMethod;

final class TagDescriptionProcessorTest extends UnitTestCase
{
    public function testProcessAddsDescriptionsOnlyWhenMissing(): void
    {
        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            new Paths(),
            tags: [
                new Tag('Customer'),
                (new Tag('Existing'))->withDescription('keep me'),
            ]
        );

        $processed = (new TagDescriptionProcessor())->process($openApi);
        $tags = [];

        foreach ($processed->getTags() as $tag) {
            $tags[$tag->getName()] = $tag->getDescription();
        }

        self::assertSame(
            'Operations related to customer management',
            $tags['Customer']
        );
        self::assertSame('keep me', $tags['Existing']);
        self::assertArrayHasKey('CustomerStatus', $tags);
        self::assertSame(
            'Health check endpoints for monitoring',
            $tags['HealthCheck']
        );
    }

    public function testProcessDoesNotOverwriteExistingKnownTagDescriptions(): void
    {
        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            new Paths(),
            tags: [
                (new Tag('Customer'))->withDescription('Custom doc'),
            ]
        );

        $processed = (new TagDescriptionProcessor())->process($openApi);
        $processedTags = $processed->getTags();

        $names = array_map(static fn (Tag $tag): string => $tag->getName(), $processedTags);
        $descriptions = array_map(
            static fn (Tag $tag): ?string => $tag->getDescription(),
            $processedTags
        );

        $customerIndex = array_search('Customer', $names, true);
        self::assertNotFalse($customerIndex);
        self::assertSame('Custom doc', $descriptions[$customerIndex]);
        self::assertSame(range(0, count($processedTags) - 1), array_keys($processedTags));
    }

    public function testIndexTagsKeepsAllPreviouslyProcessedTags(): void
    {
        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            new Paths(),
            tags: [
                new Tag('Customer'),
                new Tag('CustomerStatus'),
            ]
        );

        $method = new ReflectionMethod(TagDescriptionProcessor::class, 'indexTags');
        $method->setAccessible(true);

        $indexed = $method->invoke(new TagDescriptionProcessor(), $openApi);

        self::assertArrayHasKey('Customer', $indexed);
        self::assertArrayHasKey('CustomerStatus', $indexed);
    }
}
