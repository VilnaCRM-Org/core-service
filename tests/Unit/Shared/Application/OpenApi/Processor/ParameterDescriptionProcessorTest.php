<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\ParameterDescriptionProcessor;
use App\Tests\Unit\UnitTestCase;

final class ParameterDescriptionProcessorTest extends UnitTestCase
{
    public function testProcessAddsDescriptionsForKnownParametersOnly(): void
    {
        $parameters = [
            new Parameter('order[ulid]', 'query'),
            new Parameter('page', 'query', 'existing description'),
            new Parameter('custom', 'query'),
        ];

        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(parameters: $parameters))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $processed = (new ParameterDescriptionProcessor())->process($openApi);
        $updatedParameters = $processed
            ->getPaths()
            ->getPath('/customers')
            ->getGet()
            ->getParameters();

        self::assertSame(
            'Sort by unique identifier',
            $updatedParameters[0]->getDescription()
        );
        self::assertSame('existing description', $updatedParameters[1]->getDescription());
        self::assertSame('', $updatedParameters[2]->getDescription());
    }

    public function testProcessCoversAllDescriptionCategories(): void
    {
        $parameters = [
            new Parameter('initials[]', 'query'),
            new Parameter('createdAt[before]', 'query'),
            new Parameter('ulid[between]', 'query'),
            new Parameter('itemsPerPage', 'query'),
        ];

        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(parameters: $parameters))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $processed = (new ParameterDescriptionProcessor())->process($openApi);
        $updatedParameters = $processed
            ->getPaths()
            ->getPath('/customers')
            ->getGet()
            ->getParameters();

        self::assertSame(
            'Filter by multiple customer initials (exact match)',
            $updatedParameters[0]->getDescription()
        );
        self::assertSame(
            'Filter customers created before this date',
            $updatedParameters[1]->getDescription()
        );
        self::assertSame(
            'Filter by ULID range (comma-separated start and end)',
            $updatedParameters[2]->getDescription()
        );
        self::assertSame(
            'Number of items per page',
            $updatedParameters[3]->getDescription()
        );
    }

    public function testProcessAddsPaginationDescriptionsWhenMissing(): void
    {
        $parameters = [
            new Parameter('page', 'query'),
            new Parameter('itemsPerPage', 'query'),
        ];

        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(parameters: $parameters))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $processed = (new ParameterDescriptionProcessor())->process($openApi);
        $updatedParameters = $processed
            ->getPaths()
            ->getPath('/customers')
            ->getGet()
            ->getParameters();

        self::assertSame('Page number for pagination', $updatedParameters[0]->getDescription());
        self::assertSame('Number of items per page', $updatedParameters[1]->getDescription());
    }

    public function testProcessAddsInitialsDescription(): void
    {
        $parameters = [new Parameter('initials', 'query')];
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(parameters: $parameters))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $processed = (new ParameterDescriptionProcessor())->process($openApi);
        $updatedParameters = $processed
            ->getPaths()
            ->getPath('/customers')
            ->getGet()
            ->getParameters();

        self::assertSame(
            'Filter by customer initials (exact match)',
            $updatedParameters[0]->getDescription()
        );
    }

    public function testProcessAddsOnboardingAndTariffDescriptions(): void
    {
        $parameters = [
            new Parameter('order[position]', 'query'),
            new Parameter('code', 'query'),
            new Parameter('enabled', 'query'),
            new Parameter('priceCents[gte]', 'query'),
        ];

        $paths = new Paths();
        $paths->addPath(
            '/tariff_plans',
            (new PathItem())->withGet(new Operation(parameters: $parameters))
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', 'desc'),
            [new Server('https://localhost')],
            $paths
        );

        $processed = (new ParameterDescriptionProcessor())->process($openApi);
        $updatedParameters = $processed
            ->getPaths()
            ->getPath('/tariff_plans')
            ->getGet()
            ->getParameters();

        self::assertSame('Sort by display position', $updatedParameters[0]->getDescription());
        self::assertSame('Filter by code (exact match)', $updatedParameters[1]->getDescription());
        self::assertSame('Filter by enabled state (true/false)', $updatedParameters[2]->getDescription());
        self::assertSame(
            'Filter by price amount greater than or equal to',
            $updatedParameters[3]->getDescription()
        );
    }
}
