<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Extension;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Extension\OpenApiExtensionsApplier;
use App\Tests\Unit\UnitTestCase;

final class OpenApiExtensionsApplierTest extends UnitTestCase
{
    public function testApplyAddsExtensionProperties(): void
    {
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths()
        );

        $applier = new OpenApiExtensionsApplier();

        $result = $applier->apply($openApi, ['x-foo' => ['bar' => 'baz']]);

        $this->assertSame(
            ['x-foo' => ['bar' => 'baz']],
            $result->getExtensionProperties()
        );
        $this->assertNotSame($openApi, $result);
    }
}
