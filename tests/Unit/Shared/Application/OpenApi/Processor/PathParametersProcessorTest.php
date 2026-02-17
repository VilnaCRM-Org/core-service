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
use App\Shared\Application\OpenApi\Processor\PathParametersProcessor;
use App\Tests\Unit\Shared\Application\OpenApi\Stub\PathParameterCleanerSpy;
use App\Tests\Unit\UnitTestCase;

final class PathParametersProcessorTest extends UnitTestCase
{
    private PathParameterCleanerSpy $cleaner;
    private PathParametersProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleaner = new PathParameterCleanerSpy();
        $this->processor = new PathParametersProcessor($this->cleaner);
    }

    public function testProcessCleansPathParametersForEachOperation(): void
    {
        $pathParameter = new Parameter('id', 'path', required: true);
        $queryParameter = ['name' => 'filter'];

        $operation = new Operation(parameters: [$pathParameter, $queryParameter]);
        $paths = new Paths();
        $paths->addPath('/customers/{id}', (new PathItem())->withGet($operation));

        $openApi = new OpenApi(
            new Info('Test', '1.0', ''),
            [new Server('https://localhost')],
            $paths
        );

        $result = $this->processor->process($openApi);
        $updatedParameters = $result->getPaths()
            ->getPath('/customers/{id}')
            ->getGet()
            ->getParameters();

        self::assertSame(1, $this->cleaner->getCallCount());
        self::assertCount(2, $updatedParameters);
        self::assertInstanceOf(Parameter::class, $updatedParameters[0]);
        self::assertSame($pathParameter, $updatedParameters[0]);
        self::assertSame($queryParameter, $updatedParameters[1]);
    }

    public function testProcessLeavesOperationsWithoutParametersUntouched(): void
    {
        $paths = new Paths();
        $paths->addPath('/customers', (new PathItem())->withPost(new Operation()));

        $openApi = new OpenApi(
            new Info('Test', '1.0', ''),
            [new Server('https://localhost')],
            $paths
        );

        $result = $this->processor->process($openApi);

        self::assertNull(
            $result->getPaths()->getPath('/customers')->getPost()?->getParameters()
        );
        self::assertSame(0, $this->cleaner->getCallCount());
    }
}
