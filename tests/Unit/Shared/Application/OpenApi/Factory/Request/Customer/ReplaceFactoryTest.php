<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Customer;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\Customer\ReplaceCustomerRequestFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;

final class ReplaceFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilderMock = $this->createMock(RequestBuilder::class);

        $requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->callback(static function ($params): bool {
                if (! is_array($params) || count($params) !== 7) {
                    return false;
                }

                $indexed = [];
                foreach ($params as $parameter) {
                    if (! $parameter instanceof Parameter) {
                        return false;
                    }

                    $indexed[$parameter->name] = $parameter->example;
                }

                return isset($indexed['email'], $indexed['initials'], $indexed['phone'], $indexed['leadSource'])
                    && $indexed['email'] === SchemathesisFixtures::REPLACE_REQUEST_CUSTOMER_EMAIL
                    && $indexed['initials'] === SchemathesisFixtures::REPLACE_CUSTOMER_INITIALS
                    && $indexed['phone'] === SchemathesisFixtures::REPLACE_CUSTOMER_PHONE
                    && $indexed['leadSource'] === SchemathesisFixtures::REPLACE_CUSTOMER_LEAD_SOURCE;
            }))
            ->willReturn($this->createMock(RequestBody::class));

        $replaceFactory = new ReplaceCustomerRequestFactory($requestBuilderMock);

        $requestBody = $replaceFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
