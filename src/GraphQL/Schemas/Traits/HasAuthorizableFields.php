<?php

namespace Enjin\Platform\GraphQL\Schemas\Traits;

use GraphQL\Language\Parser;
use Illuminate\Support\Arr;
use Laragraph\Utils\RequestParser;

trait HasAuthorizableFields
{
    public function getFields(): array
    {
        $fields = parent::getFields();

        if (!config('enjin-platform.auth')) {
            return $fields;
        }

        $requests = resolve(RequestParser::class)->parseRequest(request());
        $operationName = $requests->operation;

        $names = [];
        foreach (Arr::wrap($requests) as $operation) {
            if (!$operation->query) {
                return false;
            }

            if ($documentNode = Parser::parse($operation->query)) {
                $definitions = collect($documentNode->definitions);
                $definition = $definitions->containsOneItem()
                                ? $definitions->sole()
                                : $definitions->filter(
                                    fn ($definition) => $operationName == $definition?->name?->value
                                )->first();
                $names[] = $definition?->selectionSet?->selections?->offsetGet(0)?->name?->value;
            }
        }

        return collect($fields)
            ->filter(
                fn ($field) => (auth()->check() || empty(array_intersect($names, $field['excludeFrom'] ?? [])))
                && !(($field['authRequired'] ?? false) && !auth()->check())
            )->all();
    }
}
