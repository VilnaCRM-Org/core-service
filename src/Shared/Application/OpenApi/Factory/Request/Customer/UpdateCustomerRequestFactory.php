<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Customer;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Shared\Application\OpenApi\ValueObject\Requirement;

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
        return new Parameter(
            'confirmed',
            'boolean',
            true,
            requirement: Requirement::OPTIONAL
        );
    }

    protected function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            SchemathesisFixtures::UPDATE_REQUEST_CUSTOMER_EMAIL,
            255,
            'email',
            requirement: Requirement::OPTIONAL
        );
    }

    protected function getPhoneParam(): Parameter
    {
        return new Parameter(
            'phone',
            'string',
            '0123456789',
            255,
            requirement: Requirement::OPTIONAL
        );
    }

    protected function getLeadSourceParam(): Parameter
    {
        return new Parameter(
            'leadSource',
            'string',
            'Google',
            255,
            requirement: Requirement::OPTIONAL
        );
    }

    protected function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'iri-reference',
            '/api/customer_types/' . SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID,
            255,
            requirement: Requirement::OPTIONAL
        );
    }

    protected function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'iri-reference',
            '/api/customer_statuses/' . SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID,
            255,
            requirement: Requirement::OPTIONAL
        );
    }

    protected function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'Name Surname',
            255,
            requirement: Requirement::OPTIONAL
        );
    }
}
