<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Customer;

use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;

final class UpdateFactory extends CustomerRequestFactory
{
    public function __construct(private RequestPatchBuilder $requestBuilder)
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
            required: false
        );
    }

    protected function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            'customer@example.com',
            255,
            'email',
            required: false
        );
    }

    protected function getPhoneParam(): Parameter
    {
        return new Parameter(
            'phone',
            'string',
            '0123456789',
            255,
            required: false
        );
    }

    protected function getLeadSourceParam(): Parameter
    {
        return new Parameter(
            'leadSource',
            'string',
            'Google',
            255,
            required: false
        );
    }

    protected function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'iri-reference',
            '/api/customer_types/768e998b-31cb-419d-a02c-6ae9d5b4f447',
            255,
            required: false
        );
    }

    protected function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'iri-reference',
            '/api/customer_statuses/c27f0884-8b6f-45db-858d-9a987a1d20d7',
            255,
            required: false
        );
    }

    protected function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'Name Surname',
            255,
            required: false
        );
    }
}
