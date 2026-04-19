<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerTypeUlidParameterFactory;
use App\Tests\Unit\UnitTestCase;

final class CustomerTypeFactoryTest extends UnitTestCase
{
    private UriParameterBuilder $parameterBuilder;
    private Parameter $expectedParameter;
    private CustomerTypeUlidParameterFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterBuilder = $this->createMock(UriParameterBuilder::class);
        $this->factory = new CustomerTypeUlidParameterFactory($this->parameterBuilder);
    }

    public function testGetParameterReturnsCorrectParameter(): void
    {
        $this->setupExpectedParameter();
        $this->setupParameterBuilderMock();

        $actualParameter = $this->factory->getParameter();
        $this->assertSame($this->expectedParameter, $actualParameter);
    }

    public function testGetDeleteParameterReturnsDedicatedDeleteIdentifier(): void
    {
        $deleteParameter = new Parameter(
            'ulid',
            'query',
            'CustomerType identifier',
            true,
            false,
            false,
            [
                'default' => SchemathesisFixtures::DELETE_CUSTOMER_TYPE_ID,
                'type' => 'string',
            ]
        );

        $this->parameterBuilder->expects($this->once())
            ->method('build')
            ->with(
                'ulid',
                'CustomerType identifier',
                true,
                SchemathesisFixtures::DELETE_CUSTOMER_TYPE_ID,
                'string',
                [SchemathesisFixtures::DELETE_CUSTOMER_TYPE_ID]
            )
            ->willReturn($deleteParameter);

        $this->assertSame($deleteParameter, $this->factory->getDeleteParameter());
    }

    private function setupExpectedParameter(): void
    {
        $this->expectedParameter = new Parameter(
            'ulid',
            'query',
            'CustomerType identifier',
            true,
            false,
            false,
            [
                'default' => SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID,
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
                'CustomerType identifier',
                true,
                SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID,
                'string',
                [
                    SchemathesisFixtures::CUSTOMER_TYPE_ID,
                    SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID,
                ]
            )
            ->willReturn($this->expectedParameter);
    }
}
