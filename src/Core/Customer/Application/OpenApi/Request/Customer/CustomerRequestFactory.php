<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\Request\Customer;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Factory\Request\RequestFactoryInterface;

abstract class CustomerRequestFactory implements RequestFactoryInterface
{
    public function getRequest(): RequestBody
    {
        return $this->getRequestBuilder()->build(
            $this->getDefaultParameters()
        );
    }

    abstract protected function getRequestBuilder(): RequestBuilderInterface;

    /**
     * @return array<Parameter>
     */
    protected function getDefaultParameters(): array
    {
        return [
            $this->getEmailParam(),
            $this->getPhoneParam(),
            $this->getInitialsParam(),
            $this->getLeadSourceParam(),
            $this->getTypeParam(),
            $this->getStatusParam(),
            $this->getConfirmedParam(),
        ];
    }

    protected function getConfirmedParam(): Parameter
    {
        return new Parameter(
            'confirmed',
            'boolean',
            true
        );
    }

    protected function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            'customer@example.com',
            255,
            'email'
        );
    }

    protected function getPhoneParam(): Parameter
    {
        return new Parameter(
            'phone',
            'string',
            '0123456789',
            255
        );
    }

    protected function getLeadSourceParam(): Parameter
    {
        return new Parameter(
            'leadSource',
            'string',
            'Google',
            255
        );
    }

    protected function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'iri-reference',
            '/api/customer_types/768e998b-31cb-419d-a02c-6ae9d5b4f447',
            255
        );
    }

    protected function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'iri-reference',
            '/api/customer_statuses/c27f0884-8b6f-45db-858d-9a987a1d20d7',
            255
        );
    }

    protected function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'Name Surname',
            255
        );
    }
}
