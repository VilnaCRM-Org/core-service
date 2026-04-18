<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\EventListener\ApiQueryAttributesPopulator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiQueryAttributesPopulatorTest extends UnitTestCase
{
    public function testPopulateSkipsNonArrayParameters(): void
    {
        $request = Request::create('/api/customers');

        (new ApiQueryAttributesPopulator())->populate($request, 'invalid');

        self::assertFalse($request->attributes->has('_api_query_parameters'));
        self::assertFalse($request->attributes->has('_api_filters'));
    }

    public function testPopulateSetsBothApiAttributesWhenMissing(): void
    {
        $request = Request::create('/api/customers');
        $parameters = [
            'status.value' => 'Active',
            'order' => ['status.value' => 'asc'],
        ];

        (new ApiQueryAttributesPopulator())->populate($request, $parameters);

        self::assertSame($parameters, $request->attributes->get('_api_query_parameters'));
        self::assertSame($parameters, $request->attributes->get('_api_filters'));
    }
}
