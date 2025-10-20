<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\AbstractUuidUriParameterFactory;
use App\Tests\Unit\UnitTestCase;

final class AbstractUuidUriParameterFactoryTest extends UnitTestCase
{
    private UriParameterBuilder $parameterBuilder;
    private Parameter $expectedParameter;
    private TestAbstractUuidUriParameterFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterBuilder = $this->createMock(UriParameterBuilder::class);
        $this->setupExpectedParameter();
        $this->setupParameterBuilderMock();
        $this->factory = new TestAbstractUuidUriParameterFactory($this->parameterBuilder);
    }

    public function testGetParameterReturnsCorrectParameter(): void
    {
        $actualParameter = $this->factory->getParameter();
        $this->assertSame($this->expectedParameter, $actualParameter);
    }

    public function testGetDescriptionReturnsDefaultDescription(): void
    {
        // The getDescription method is called internally by getParameter
        // We can verify it works by checking that the parameter is built with the correct description
        $parameter = $this->factory->getParameter();
        $this->assertSame('Customer identifier', $parameter->getDescription());
    }

    private function setupExpectedParameter(): void
    {
        $this->expectedParameter = new Parameter(
            'ulid',
            'query',
            'Customer identifier',
            true,
            false,
            false,
            [
                'default' => '01JKX8XGHVDZ46MWYMZT94YER4',
                'type' => 'string',
            ]
        );
    }

    private function setupParameterBuilderMock(): void
    {
        $this->parameterBuilder->expects($this->once())
            ->method('build')
            ->with(
                'ulid',
                'Customer identifier',
                true,
                '01JKX8XGHVDZ46MWYMZT94YER4',
                'string'
            )
            ->willReturn($this->expectedParameter);
    }
}

class TestAbstractUuidUriParameterFactory extends AbstractUuidUriParameterFactory
{
    // This class extends the abstract class to test it
    // The getDescription method is not overridden, so it uses the default implementation
}
