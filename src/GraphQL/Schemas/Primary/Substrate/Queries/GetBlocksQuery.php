<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Interfaces\PlatformPublicGraphQlOperation;
use Enjin\Platform\Models\Block;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetBlocksQuery extends Query implements PlatformGraphQlQuery, PlatformPublicGraphQlOperation
{
    use InPrimarySubstrateSchema;

    protected $middleware = [
        ResolvePage::class,
        SingleFilterOnly::class,
    ];

    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetBlocks',
            'description' => __('enjin-platform::query.get_blocks.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('Block', 'BlockConnection');
    }

    /**
     * Get the query's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'ids' => [
                'type' => GraphQL::type('[String]'),
                'description' => '',
                'singleFilter' => true,
            ],
            'numbers' => [
                'type' => GraphQL::type('[Int]'),
                'description' => __('enjin-platform::query.get_blocks.args.number'),
                'singleFilter' => true,
            ],
            'hashes' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_blocks.args.hashes'),
                'singleFilter' => true,
            ],
        ]);
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return Block::selectFields($getSelectFields)
            ->when(!empty($args['ids']), fn (Builder $query) => $query->whereIn('block_hash', $args['ids']))
            ->when(!empty($args['numbers']), fn (Builder $query) => $query->whereIn('block_number', $args['numbers']))
            ->when(!empty($args['hashes']), fn (Builder $query) => $query->whereIn('block_hash', $args['hashes']))
            ->cursorPaginateWithTotalDesc('block_number', $args['first']);
    }

    /**
     * Get the validation rules.
     */
    #[Override]
    protected function rules(array $args = []): array
    {
        return [
            'ids' => ['nullable', 'max:100', 'distinct', new ValidHex(32)],
            'numbers' => ['nullable', 'max:100', 'distinct', new MinBigInt(), new MaxBigInt(Hex::MAX_UINT128)],
            'hashes' => ['nullable', 'max:100', 'distinct', new ValidHex(32)],
        ];
    }
}
