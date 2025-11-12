<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ArrayObject;

final class ArrayContextBuilder
{
    /**
     * @param array<Parameter> $params
     */
    public function build(array $params): ArrayObject
    {
        if (count($params) === 0) {
            return new ArrayObject([
                'application/ld+json' => [
                    'example' => [''],
                ],
            ]);
        }

        $collection = $this->buildParamsCollection($params);

        return $this->buildContent(
            $collection['items'],
            $collection['example'],
            $collection['required']
        );
    }

    /**
     * @param array<Parameter> $params
     *
     * @return array{
     *     items: array<string, string>,
     *     example: array<string, string|int|array|bool|null>,
     *     required: array<string>
     * }
     */
    private function buildParamsCollection(array $params): array
    {
        return [
            'items' => $this->buildItems($params),
            'example' => $this->buildExample($params),
            'required' => $this->collectRequired($params),
        ];
    }

    /**
     * @param array<Parameter> $params
     *
     * @return array<string, array<string, string|int>>
     */
    private function buildItems(array $params): array
    {
        return array_reduce(
            $params,
            static function (array $carry, Parameter $param): array {
                $carry[$param->name] = array_filter(
                    [
                        'type' => $param->type,
                        'maxLength' => $param->maxLength,
                        'format' => $param->format,
                    ],
                    static fn ($value) => $value !== null
                );

                return $carry;
            },
            []
        );
    }

    /**
     * @param array<Parameter> $params
     *
     * @return array<string, string|int|array|bool|null>
     */
    private function buildExample(array $params): array
    {
        return array_reduce(
            $params,
            static function (array $carry, Parameter $param): array {
                $carry[$param->name] = $param->example;

                return $carry;
            },
            []
        );
    }

    /**
     * @param array<Parameter> $params
     *
     * @return array<int, string>
     */
    private function collectRequired(array $params): array
    {
        return array_map(
            static fn (Parameter $param): string => $param->name,
            array_filter(
                $params,
                static fn (Parameter $param): bool => $param->isRequired()
            )
        );
    }

    /**
     * @param array<string, string> $items
     * @param array<string, string|int|array|bool|null> $example
     * @param array<string> $required
     */
    private function buildContent(
        array $items,
        array $example,
        array $required
    ): ArrayObject {
        return new ArrayObject([
            'application/ld+json' => [
                'schema' => [
                    'type' => 'array',
                    'items' => ['properties' => $items],
                    'required' => $required,
                ],
                'example' => [$example],
            ],
        ]);
    }
}
