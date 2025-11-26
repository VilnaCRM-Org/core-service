<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\CustomerType;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Factory\Request\RequestFactoryInterface;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

abstract class CustomerTypeRequestFactory implements
    RequestFactoryInterface
{
    private const DEFAULT_VALUE = 'Prospect';
    private const DEFAULT_MAX_LENGTH = 255;

    public function __construct(
        private readonly string $defaultValue = self::DEFAULT_VALUE,
        private readonly int $maxLength = self::DEFAULT_MAX_LENGTH
    ) {
    }

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
            $this->getValueParam(),
        ];
    }

    protected function getValueParam(): Parameter
    {
        return new Parameter(
            'value',
            'string',
            $this->defaultValue,
            $this->maxLength
        );
    }
}
