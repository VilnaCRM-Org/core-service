<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Customer;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

final class UpdateCustomerRequestFactory extends CustomerRequestFactory
{
    public function __construct(private RequestBuilderInterface $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }

    protected function getConfirmedParam(): Parameter
    {
        return Parameter::optional(
            'confirmed',
            'boolean',
            true
        );
    }

    protected function getEmailParam(): Parameter
    {
        return Parameter::optional(
            'email',
            'string',
            SchemathesisFixtures::UPDATE_CUSTOMER_EMAIL,
            255,
            'email'
        );
    }

    protected function getPhoneParam(): Parameter
    {
        return Parameter::optional(
            'phone',
            'string',
            '0123456789',
            255
        );
    }

    protected function getLeadSourceParam(): Parameter
    {
        return Parameter::optional(
            'leadSource',
            'string',
            'Google',
            255
        );
    }

    protected function getTypeParam(): Parameter
    {
        return Parameter::optional(
            'type',
            'iri-reference',
            '/api/customer_types/' . SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID,
            255
        );
    }

    protected function getStatusParam(): Parameter
    {
        return Parameter::optional(
            'status',
            'iri-reference',
            '/api/customer_statuses/' . SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID,
            255
        );
    }

    protected function getInitialsParam(): Parameter
    {
        return Parameter::optional(
            'initials',
            'string',
            'Name Surname',
            255
        );
    }
}
