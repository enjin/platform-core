<?php

namespace Enjin\Platform\Middlewares;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;
use Rebing\GraphQL\Support\OperationParams;

class UniqueFieldNamesArray extends AbstractExecutionMiddleware
{
    /**
     * The errors.
     */
    protected array $errors = [];

    /**
     * Handle's middleware logic.
     */
    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        $documentNode = Parser::parse($params->query);
        $operationName = $params->operation;
        $operationDefinitionNode = collect($documentNode->definitions)->where('name.value', '=', $operationName)->first();
        if (isset($operationDefinitionNode)) {
            collect($operationDefinitionNode->selectionSet->selections[0]->arguments)
                ->each(fn ($args) => $this->validateUniqueFieldNames(collect(Arr::wrap($args))));
        }

        if ($this->errors) {
            return new ExecutionResult(null, $this->errors);
        }

        return $next($schemaName, $schema, $params, $rootValue, $contextValue);
    }

    protected function validateUniqueFieldNames($nodes)
    {
        $fieldNames = $nodes->map(fn ($node) => $node->name->value);
        $duplicates = $fieldNames->duplicates();
        if ($duplicates->isNotEmpty()) {
            $duplicates->each(fn ($duplicate) => $this->errors[$duplicate] = new Error(__('enjin-platform::error.there_can_only_one_input_name', ['name' => $duplicate])));
        }

        $nodes->each(function ($node) {
            switch ($node->value->kind) {
                case NodeKind::OBJECT:
                    $this->validateUniqueFieldNames(collect($node->value->fields));

                    break;
                case NodeKind::LST:
                    collect($node->value->values->getIterator())
                        ->each(fn ($value) => $this->validateUniqueFieldNames(collect($value->fields ?? [])));

                    break;
            }
        });
    }
}
