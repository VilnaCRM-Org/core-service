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
     * @return array{items: array<string, string>, example: array<string, mixed>, required: array<string>}
     */
    private function buildParamsCollection(array $params): array
    {
        $items = [];
        $example = [];
        $required = [];

        foreach ($params as $param) {
            if ($param->isRequired()) {
                $required[] = $param->name;
            }
            $this->addParameterToItems($items, $param);
            $example[$param->name] = $param->example;
        }

        return [
            'items' => $items,
            'example' => $example,
            'required' => $required,
        ];
    }

    /**
     * @param array<string, string> $items
     */
    private function addParameterToItems(array &$items, Parameter $param): void
    {
        $items[$param->name] = array_filter(
            [
                'type' => $param->type,
                'maxLength' => $param->maxLength,
                'format' => $param->format,
            ],
            static fn ($value) => $value !== null
        );
    }

    /**
     * @param array<string, string> $items
     * @param array<string, string|int|array> $example
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
