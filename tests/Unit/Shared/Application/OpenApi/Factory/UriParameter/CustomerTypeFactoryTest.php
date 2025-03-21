<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerTypeFactory;
use PHPUnit\Framework\TestCase;

final class CustomerTypeFactoryTest extends TestCase
{
    private UriParameterBuilder $parameterBuilder;
    private Parameter $expectedParameter;
    private CustomerTypeFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterBuilder = $this->createMock(UriParameterBuilder::class);
        $this->setupExpectedParameter();
        $this->setupParameterBuilderMock();
        $this->factory = new CustomerTypeFactory($this->parameterBuilder);
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
                'default' => '01JKX8XGHVDZ46MWYMZT94YER4',
                'type' => 'string'
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
                '01JKX8XGHVDZ46MWYMZT94YER4',
                'string'
            )
            ->willReturn($this->expectedParameter);
    }

    public function testGetParameterReturnsCorrectParameter(): void
    {
        $actualParameter = $this->factory->getParameter();
        $this->assertSame($this->expectedParameter, $actualParameter);
    }
}
