<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\TagCollectorProcessor;
use App\Tests\Unit\UnitTestCase;
use ReflectionProperty;

final class TagCollectorProcessorTest extends UnitTestCase
{
    public function testProcessCollectsAndSortsAllUniqueTags(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())
                ->withGet(new Operation(tags: ['Customer']))
                ->withPost(new Operation(tags: ['CustomerStatus', 'Customer']))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths,
            tags: [new Tag('Existing')]
        );

        $processed = (new TagCollectorProcessor())->process($openApi);

        $tagNames = array_map(
            static fn (Tag $tag): string => $tag->getName(),
            $processed->getTags()
        );

        self::assertSame(['Customer', 'CustomerStatus', 'Existing'], $tagNames);
    }

    public function testProcessRemovesDuplicateOperationTags(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())
                ->withPost(new Operation(tags: ['Customer', 'Customer']))
                ->withPut(new Operation(tags: ['Customer']))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $processed = (new TagCollectorProcessor())->process($openApi);
        $tagNames = array_map(
            static fn (Tag $tag): string => $tag->getName(),
            $processed->getTags()
        );

        self::assertSame($tagNames, array_values(array_unique($tagNames)));
    }

    public function testProcessIncludesTagsFromGetOperations(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(tags: ['Customer']))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $processed = (new TagCollectorProcessor())->process($openApi);
        $tagNames = array_map(
            static fn (Tag $tag): string => $tag->getName(),
            $processed->getTags()
        );

        self::assertContains('Customer', $tagNames);
    }

    public function testProcessDeduplicatesExistingAndOperationTags(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(tags: ['Customer']))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths,
            tags: [new Tag('Customer')]
        );

        $processed = (new TagCollectorProcessor())->process($openApi);
        $tagNames = array_map(
            static fn (Tag $tag): string => $tag->getName(),
            $processed->getTags()
        );

        self::assertSame(['Customer'], $tagNames);
    }

    public function testProcessRemovesDuplicateExistingTags(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(tags: []))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths,
            tags: [new Tag('Customer'), new Tag('Customer')]
        );

        $processed = (new TagCollectorProcessor())->process($openApi);
        $tagNames = array_map(
            static fn (Tag $tag): string => $tag->getName(),
            $processed->getTags()
        );

        self::assertSame(['Customer'], $tagNames);
    }

    public function testProcessIgnoresNonPathItemsDuringCollection(): void
    {
        $paths = new Paths();
        $validPathItem = (new PathItem())->withGet(new Operation(tags: ['Customer']));
        $paths->addPath('/customers', $validPathItem);

        $pathsProperty = new ReflectionProperty(Paths::class, 'paths');
        /** @psalm-suppress UnusedMethodCall */
        $pathsProperty->setAccessible(true);
        $pathsProperty->setValue($paths, [
            '/customers' => $validPathItem,
            '/invalid' => null,
        ]);

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $tagNames = array_map(
            static fn (Tag $tag): string => $tag->getName(),
            (new TagCollectorProcessor())->process($openApi)->getTags()
        );

        self::assertSame(['Customer'], $tagNames);
    }

    public function testProcessMergesTagsFromMultiplePathItems(): void
    {
        $paths = new Paths();
        $paths->addPath('/customers', (new PathItem())->withGet(new Operation(tags: ['Customer'])));
        $paths->addPath('/statuses', (new PathItem())->withGet(new Operation(tags: ['Status'])));

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $tagNames = array_map(
            static fn (Tag $tag): string => $tag->getName(),
            (new TagCollectorProcessor())->process($openApi)->getTags()
        );

        self::assertSame(['Customer', 'Status'], $tagNames);
    }

    public function testProcessCollectsTagsFromAllOperationsWithinPathItem(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())
                ->withGet(new Operation(tags: ['GetOnly']))
                ->withPost(new Operation(tags: ['PostOnly']))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $tagNames = array_map(
            static fn (Tag $tag): string => $tag->getName(),
            (new TagCollectorProcessor())->process($openApi)->getTags()
        );

        sort($tagNames);

        self::assertSame(['GetOnly', 'PostOnly'], $tagNames);
    }
}
