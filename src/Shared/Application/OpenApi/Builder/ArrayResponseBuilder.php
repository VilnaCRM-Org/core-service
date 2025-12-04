<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\ValueObject\Header;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use ArrayObject;

final class ArrayResponseBuilder implements ResponseBuilderInterface
{
    public function __construct(private ArrayContextBuilder $contextBuilder)
    {
    }

    /**
     * @param array<Parameter> $params
     * @param array<Header> $headers
     */
    public function build(
        string $description,
        array $params,
        array $headers
    ): Response {
        return new Response(
            description: $description,
            content: $this->contextBuilder->build($params),
            headers: $this->buildHeadersArray($headers)
        );
    }

    /**
     * @param array<Header> $headers
     */
    private function buildHeadersArray(array $headers): ArrayObject
    {
        $headersArray = array_reduce(
            $headers,
            fn (array $collection, Header $header): array => $this
                ->appendHeader($collection, $header),
            []
        );

        return new ArrayObject($headersArray);
    }

    private function createHeaderModel(Header $header): Model\Header
    {
        return new Model\Header(
            description: $header->description,
            schema: [
                'type' => $header->type,
                'format' => $header->format,
                'example' => $header->example,
            ]
        );
    }

    /**
     * @param array<string, Model\Header> $collection
     *
     * @return array<string, Model\Header>
     */
    private function appendHeader(array $collection, Header $header): array
    {
        $collection[$header->name] = $this->createHeaderModel($header);

        return $collection;
    }
}
