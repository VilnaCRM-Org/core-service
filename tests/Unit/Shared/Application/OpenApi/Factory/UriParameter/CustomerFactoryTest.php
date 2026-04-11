<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerUlidParameterFactory;
use App\Tests\Unit\UnitTestCase;

final class CustomerFactoryTest extends UnitTestCase
{
    private UriParameterBuilder $parameterBuilder;
    private Parameter $expectedParameter;
    private CustomerUlidParameterFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterBuilder = $this->createMock(UriParameterBuilder::class);
        $this->setupExpectedParameter();
        $this->setupParameterBuilderMock();
        $this->factory = new CustomerUlidParameterFactory($this->parameterBuilder);
    }

    public function testGetParameterReturnsCorrectParameter(): void
    {
        $actualParameter = $this->factory->getParameter();
        $this->assertSame($this->expectedParameter, $actualParameter);
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
                'default' => SchemathesisFixtures::UPDATE_CUSTOMER_ID,
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
                SchemathesisFixtures::UPDATE_CUSTOMER_ID,
                'string'
            )
            ->willReturn($this->expectedParameter);
    }
}
