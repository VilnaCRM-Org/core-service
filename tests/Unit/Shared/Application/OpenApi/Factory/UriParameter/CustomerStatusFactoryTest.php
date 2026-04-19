<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerStatusUlidParameterFactory;
use PHPUnit\Framework\TestCase;

final class CustomerStatusFactoryTest extends TestCase
{
    private UriParameterBuilder $parameterBuilder;
    private Parameter $expectedParameter;
    private CustomerStatusUlidParameterFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterBuilder = $this->createMock(UriParameterBuilder::class);
        $this->factory = new CustomerStatusUlidParameterFactory($this->parameterBuilder);
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
            'CustomerStatus identifier',
            true,
            false,
            false,
            [
                'default' => SchemathesisFixtures::DELETE_CUSTOMER_STATUS_ID,
                'type' => 'string',
            ]
        );

        $this->parameterBuilder->expects($this->once())
            ->method('build')
            ->with(
                'ulid',
                'CustomerStatus identifier',
                true,
                SchemathesisFixtures::DELETE_CUSTOMER_STATUS_ID,
                'string',
                [SchemathesisFixtures::DELETE_CUSTOMER_STATUS_ID]
            )
            ->willReturn($deleteParameter);

        $this->assertSame($deleteParameter, $this->factory->getDeleteParameter());
    }

    private function setupExpectedParameter(): void
    {
        $this->expectedParameter = new Parameter(
            'ulid',
            'query',
            'CustomerStatus identifier',
            true,
            false,
            false,
            [
                'default' => SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID,
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
                'CustomerStatus identifier',
                true,
                SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID,
                'string',
                [
                    SchemathesisFixtures::CUSTOMER_STATUS_ID,
                    SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID,
                ]
            )
            ->willReturn($this->expectedParameter);
    }
}
