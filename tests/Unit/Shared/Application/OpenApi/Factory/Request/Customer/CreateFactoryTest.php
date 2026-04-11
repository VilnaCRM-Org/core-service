<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Customer;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\Customer\CreateCustomerRequestFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;

final class CreateFactoryTest extends UnitTestCase
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

                return $indexed['email'] === SchemathesisFixtures::CREATE_REQUEST_CUSTOMER_EMAIL
                    && $indexed['type'] === '/api/customer_types/' . SchemathesisFixtures::CUSTOMER_TYPE_ID
                    && $indexed['status'] === '/api/customer_statuses/' . SchemathesisFixtures::CUSTOMER_STATUS_ID;
            }))
            ->willReturn($this->createMock(RequestBody::class));

        $createFactory = new CreateCustomerRequestFactory($requestBuilderMock);

        $requestBody = $createFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
