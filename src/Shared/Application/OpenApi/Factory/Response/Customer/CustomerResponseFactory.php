<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response\Customer;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilderInterface;
use App\Shared\Application\OpenApi\Factory\Response\ResponseFactoryInterface;

abstract class CustomerResponseFactory implements ResponseFactoryInterface
{
    abstract protected function getResponseBuilder(): ResponseBuilderInterface;

    abstract protected function getTitle(): string;

    public function getResponse(): Response
    {
        return $this->getResponseBuilder()->build(
            $this->getTitle(),
            $this->getDefaultParameters(),
            []
        );
    }

    protected function getDefaultParameters(): array
    {
        return [
            $this->getCreatedAtParam(),
            $this->getUpdatedAtParam(),
            $this->getConfirmedParam(),
            $this->getEmailParam(),
            $this->getPhoneParam(),
            $this->getInitialsParam(),
            $this->getLeadSourceParam(),
            $this->getTypeParam(),
            $this->getStatusParam(),
            $this->getIdParam(),
        ];
    }

    protected function getCreatedAtParam(): Parameter
    {
        return new Parameter('createdAt', 'date', '2024-12-20T16:53:12+00:00');
    }

    protected function getUpdatedAtParam(): Parameter
    {
        return new Parameter('updatedAt', 'date', '2024-12-20T16:53:12+00:00');
    }

    protected function getConfirmedParam(): Parameter
    {
        return new Parameter('confirmed', 'boolean', true);
    }

    protected function getEmailParam(): Parameter
    {
        return new Parameter('email', 'string', 'customer@example.com');
    }

    protected function getPhoneParam(): Parameter
    {
        return new Parameter('phone', 'string', '0123456789');
    }

    protected function getLeadSourceParam(): Parameter
    {
        return new Parameter('leadSource', 'string', 'Google');
    }

    protected function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'iri-reference',
            '/api/customer_types/768e998b-31cb-419d-a02c-6ae9d5b4f447'
        );
    }

    protected function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'iri-reference',
            '/api/customer_statuses/c27f0884-8b6f-45db-858d-9a987a1d20d7'
        );
    }

    protected function getInitialsParam(): Parameter
    {
        return new Parameter('initials', 'string', 'Name Surname');
    }

    protected function getIdParam(): Parameter
    {
        return new Parameter('id', 'string', '018dd6ba-e901-7a8c-b27d-65d122caca6b');
    }
}
