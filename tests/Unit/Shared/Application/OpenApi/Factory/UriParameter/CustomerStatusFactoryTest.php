<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerStatusFactory;
use PHPUnit\Framework\TestCase;

final class CustomerStatusFactoryTest extends TestCase
{
    public function testGetParameterReturnsCorrectParameter(): void
    {
        $parameterBuilder = $this->createMock(UriParameterBuilder::class);

        $expectedParameter = new Parameter(
            'ulid',
            'query',
            'CustomerStatus identifier',
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
                'CustomerStatus identifier',
                true,
                '01JKX8XGHVDZ46MWYMZT94YER4',
                'string'
            )
            ->willReturn($expectedParameter);

        $factory = new CustomerStatusFactory($parameterBuilder);
        $actualParameter = $factory->getParameter();

        $this->assertSame($expectedParameter, $actualParameter);
    }
}
