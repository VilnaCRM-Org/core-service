<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final class ArrayContextBuilder
{
    /**
     * @param array<Parameter> $params
     */
    public function build(array $params): \ArrayObject
    {
        $content = new \ArrayObject([
            'application/ld+json' => [
                'example' => [''],
            ],
        ]);

        if (count($params) > 0) {
            $items = [];
            $example = [];
            $required = [];

            foreach ($params as $param) {
                if ($param->required) {
                    $required[] = $param->name;
                }
                $this->addParameterToItems($items, $param);
                $example[$param->name] = $param->example;
            }

            $content = $this->buildContent($items, $example, $required);
        }

        return $content;
    }

    /**
     * @param array<string, string> $items
     */
    private function addParameterToItems(array &$items, Parameter $param): void
    {
        $items[$param->name] = [
            'type' => $param->type,
            'maxLength' => $param->maxLength,
            'format' => $param->format,
        ];
    }

    /**
     * @param array<string, string> $items
     * @param array<string, string|int|array> $example
     * @param array<string> $required
     *
     * @psalm-return \ArrayObject<'application/ld+json', array{schema: array{type: 'array', items: array{properties: array<string, string>}, required: array<string>}, example: list{array<string, array|int|string>}}>
     */
    private function buildContent(
        array $items,
        array $example,
        array $required
    ): \ArrayObject {
        return new \ArrayObject([
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
