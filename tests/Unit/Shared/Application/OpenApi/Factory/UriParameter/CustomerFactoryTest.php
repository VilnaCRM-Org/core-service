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
        $this->factory = new CustomerUlidParameterFactory($this->parameterBuilder);
    }

    public function testGetParameterReturnsCorrectParameter(): void
    {
        $this->setupParameterBuilderMock();

        $actualParameter = $this->factory->getParameter();
        $this->assertSame($this->expectedParameter, $actualParameter);
    }

    public function testGetDeleteParameterReturnsDedicatedDeleteIdentifier(): void
    {
        $deleteParameter = new Parameter(
            'ulid',
            'query',
            'Customer identifier',
            true,
            false,
            false,
            [
                'default' => SchemathesisFixtures::DELETE_CUSTOMER_ID,
                'type' => 'string',
            ]
        );

        $this->parameterBuilder->expects($this->once())
            ->method('build')
            ->with(
                'ulid',
                'Customer identifier',
                true,
                SchemathesisFixtures::DELETE_CUSTOMER_ID,
                'string',
                [SchemathesisFixtures::DELETE_CUSTOMER_ID]
            )
            ->willReturn($deleteParameter);

        $this->assertSame($deleteParameter, $this->factory->getDeleteParameter());
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
                'string',
                [
                    SchemathesisFixtures::CUSTOMER_ID,
                    SchemathesisFixtures::UPDATE_CUSTOMER_ID,
                    SchemathesisFixtures::REPLACE_CUSTOMER_ID,
                    SchemathesisFixtures::GET_CUSTOMER_ID,
                ]
            )
            ->willReturn($this->expectedParameter);
    }
}
