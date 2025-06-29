<?php

namespace Enjin\Platform\GraphQL\Schemas\Traits;

use Enjin\Platform\Middlewares\OperationDefinitionNodeStore;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use GraphQL\Server\RequestError;
use Illuminate\Support\Arr;
use JsonException;
use Laragraph\Utils\RequestParser;

trait HasAuthorizableFields
{
    /**
     * @throws SyntaxError
     * @throws RequestError
     * @throws JsonException
     */
    public function getFields(): array
    {
        $fields = parent::getFields();

        if (!config('enjin-platform.auth')) {
            return $fields;
        }

        if (app()->runningUnitTests()) {
            $names = [OperationDefinitionNodeStore::getOperationName()];
        } else {
            $requests = resolve(RequestParser::class)->parseRequest(request());
            $operationName = $requests->operation;

            $names = [];
            foreach (Arr::wrap($requests) as $operation) {
                if (!$operation->query) {
                    return [];
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
        }

        return collect($fields)
            ->filter(
                fn ($field) => (auth()->check() || empty(array_intersect($names, $field['excludeFrom'] ?? [])))
                && !(($field['authRequired'] ?? false) && !auth()->check())
            )->all();
    }
}
