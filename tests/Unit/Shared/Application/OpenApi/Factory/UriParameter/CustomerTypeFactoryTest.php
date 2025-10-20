<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriCustTy;
use App\Tests\Unit\UnitTestCase;

final class CustomerTypeFactoryTest extends UnitTestCase
{
    private UriParameterBuilder $parameterBuilder;
    private Parameter $expectedParameter;
    private UuidUriCustTy $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterBuilder = $this->createMock(UriParameterBuilder::class);
        $this->setupExpectedParameter();
        $this->setupParameterBuilderMock();
        $this->factory = new UuidUriCustTy($this->parameterBuilder);
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
            'CustomerType identifier',
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
                'CustomerType identifier',
                true,
                '01JKX8XGHVDZ46MWYMZT94YER4',
                'string'
            )
            ->willReturn($this->expectedParameter);
    }
}
