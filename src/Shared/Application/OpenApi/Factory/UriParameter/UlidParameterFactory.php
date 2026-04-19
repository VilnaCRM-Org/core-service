<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;

abstract class UlidParameterFactory implements UriParameterFactoryInterface
{
    public function __construct(private UriParameterBuilder $parameterBuilder)
    {
    }

    public function getParameter(): Parameter
    {
        return $this->buildParameter(
            $this->getExampleUlid(),
            $this->getAllowedUlids()
        );
    }

    public function getDeleteParameter(): Parameter
    {
        return $this->buildParameter(
            $this->getDeleteUlid(),
            [$this->getDeleteUlid()]
        );
    }

    /**
     * @return array<int, string>
     */
    protected function getAllowedUlids(): array
    {
        return [$this->getExampleUlid()];
    }

    protected function getDeleteUlid(): string
    {
        return $this->getExampleUlid();
    }

    abstract protected function getDescription(): string;

    abstract protected function getExampleUlid(): string;

    /**
     * @param array<int, string> $allowedUlids
     */
    private function buildParameter(string $example, array $allowedUlids): Parameter
    {
        return $this->parameterBuilder->build(
            'ulid',
            $this->getDescription(),
            true,
            $example,
            'string',
            $allowedUlids
        );
    }
}
