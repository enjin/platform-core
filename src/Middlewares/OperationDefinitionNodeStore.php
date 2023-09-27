<?php

namespace Enjin\Platform\Middlewares;

use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use Rebing\GraphQL\Support\ExecutionMiddleware\AbstractExecutionMiddleware;
use Rebing\GraphQL\Support\OperationParams;

class OperationDefinitionNodeStore extends AbstractExecutionMiddleware
{
    /**
     * The current request's Operation Definition Node.
     */
    public static OperationDefinitionNode $operationDefinitionNode;

    /**
     * Handle's middleware logic.
     */
    public function handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult
    {
        $documentNode = Parser::parse($params->query);
        $operationName = $params->operation;
        static::$operationDefinitionNode = collect($documentNode->definitions)
            ->when($operationName, fn ($collection) => $collection->where('name.value', '=', $operationName))
            ->first();

        return $next($schemaName, $schema, $params, $rootValue, $contextValue);
    }

    /**
     * Get the current request's operation name.
     */
    public static function getOperationName()
    {
        return static::$operationDefinitionNode
            ->getSelectionSet()
            ->selections
            ->offsetGet(0)
            ->name->value;
    }
}
