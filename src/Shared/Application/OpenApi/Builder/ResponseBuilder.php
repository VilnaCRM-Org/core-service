<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ArrayObject;

final class ResponseBuilder implements ResponseBuilderInterface
{
    public function __construct(private ContextBuilder $contextBuilder)
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
        $headersArray = new ArrayObject();

        foreach ($headers as $header) {
            $headersArray[$header->name] = new Model\Header(
                description: $header->description,
                schema: [
                    'type' => $header->type,
                    'format' => $header->format,
                    'example' => $header->example,
                ]
            );
        }

        return $headersArray;
    }
}
