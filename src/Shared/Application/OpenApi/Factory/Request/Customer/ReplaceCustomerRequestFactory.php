<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Customer;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

final class ReplaceCustomerRequestFactory extends CustomerRequestFactory
{
    public function __construct(private RequestBuilderInterface $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }

    protected function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            SchemathesisFixtures::REPLACE_REQUEST_CUSTOMER_EMAIL,
            255,
            'email'
        );
    }

    protected function getPhoneParam(): Parameter
    {
        return new Parameter(
            'phone',
            'string',
            SchemathesisFixtures::REPLACE_CUSTOMER_PHONE,
            255
        );
    }

    protected function getLeadSourceParam(): Parameter
    {
        return new Parameter(
            'leadSource',
            'string',
            SchemathesisFixtures::REPLACE_CUSTOMER_LEAD_SOURCE,
            255
        );
    }

    protected function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            SchemathesisFixtures::REPLACE_CUSTOMER_INITIALS,
            255
        );
    }
}
