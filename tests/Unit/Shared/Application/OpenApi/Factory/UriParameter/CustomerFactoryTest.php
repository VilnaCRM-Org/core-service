<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerFactory;
use PHPUnit\Framework\TestCase;

final class CustomerFactoryTest extends TestCase
{
    public function testGetParameterReturnsCorrectParameter(): void
    {
        $parameterBuilder = $this->createMock(UriParameterBuilder::class);

        $expectedParameter = new Parameter(
            'ulid',
            'query',
            'Customer identifier',
            true,
            false,
            false,
            [
                'default' => '01JKX8XGHVDZ46MWYMZT94YER4',
                'type' => 'string'
            ]
        );

        $parameterBuilder->expects($this->once())
            ->method('build')
            ->with(
                'ulid',
                'Customer identifier',
                true,
                '01JKX8XGHVDZ46MWYMZT94YER4',
                'string'
            )
            ->willReturn($expectedParameter);

        $factory = new CustomerFactory($parameterBuilder);
        $actualParameter = $factory->getParameter();

        $this->assertSame($expectedParameter, $actualParameter);
    }
}
