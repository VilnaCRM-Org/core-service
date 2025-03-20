<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerTypeFactory;
use PHPUnit\Framework\TestCase;

final class CustomerTypeFactoryTest extends TestCase
{
    public function testGetParameterReturnsCorrectParameter(): void
    {
        $parameterBuilder = $this->createMock(UriParameterBuilder::class);

        $expectedParameter = new Parameter(
            'ulid',
            'query',
            'CustomerType identifier',
            true,
            false,
            false,
            [
                'default' => '01JKX8XGHVDZ46MWYMZT94YER4',
                'type'    => 'string'
            ]
        );

        $parameterBuilder->expects($this->once())
            ->method('build')
            ->with(
                'ulid',
                'CustomerType identifier',
                true,
                '01JKX8XGHVDZ46MWYMZT94YER4',
                'string'
            )
            ->willReturn($expectedParameter);

        $factory = new CustomerTypeFactory($parameterBuilder);
        $actualParameter = $factory->getParameter();

        $this->assertSame($expectedParameter, $actualParameter);
    }
}
