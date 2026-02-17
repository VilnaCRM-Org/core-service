<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Customer;

use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

final class UpdateCustomerRequestFactory extends CustomerRequestFactory
{
    public function __construct(private RequestBuilderInterface $requestBuilder)
    {
    }

    #[Override]
    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }

    #[Override]
    protected function getConfirmedParam(): Parameter
    {
        return Parameter::optional(
            'confirmed',
            'boolean',
            true
        );
    }

    #[Override]
    protected function getEmailParam(): Parameter
    {
        return Parameter::optional(
            'email',
            'string',
            'customer@example.com',
            255,
            'email'
        );
    }

    #[Override]
    protected function getPhoneParam(): Parameter
    {
        return Parameter::optional(
            'phone',
            'string',
            '0123456789',
            255
        );
    }

    #[Override]
    protected function getLeadSourceParam(): Parameter
    {
        return Parameter::optional(
            'leadSource',
            'string',
            'Google',
            255
        );
    }

    #[Override]
    protected function getTypeParam(): Parameter
    {
        return Parameter::optional(
            'type',
            'iri-reference',
            '/api/customer_types/768e998b-31cb-419d-a02c-6ae9d5b4f447',
            255
        );
    }

    #[Override]
    protected function getStatusParam(): Parameter
    {
        return Parameter::optional(
            'status',
            'iri-reference',
            '/api/customer_statuses/c27f0884-8b6f-45db-858d-9a987a1d20d7',
            255
        );
    }

    #[Override]
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
