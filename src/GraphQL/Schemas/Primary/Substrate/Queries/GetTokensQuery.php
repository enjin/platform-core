<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Base\Query;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetTokensQuery extends Query implements PlatformGraphQlQuery
{
    use HasEncodableTokenId;
    use InPrimarySubstrateSchema;

    protected $middleware = [
        SingleFilterOnly::class,
        ResolvePage::class,
    ];

    /**
     * Get the query's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetTokens',
            'description' => __('enjin-platform::query.get_tokens.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('Token', 'TokenConnection');
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
            'collectionId' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::query.get_tokens.args.collectionId'),
                'singleFilter' => true,
            ],
            'tokenIds' => [
                'type' => GraphQL::type('[EncodableTokenIdInput]'),
                'description' => __('enjin-platform::query.get_tokens.args.tokenIds'),
            ],
        ]);
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return Token::selectFields($getSelectFields)
            ->when(!empty($args['ids']), fn (Builder $query) => $query->whereIn('id', $args['ids']))
            ->when(!empty($args['collectionId']) && empty($args['tokenIds']), fn (Builder $query) => $query->where('collection_id', $args['collectionId']))
            ->when(
                !empty($args['collectionId']) && !empty($args['tokenIds']),
                fn (Builder $query) => $query
                    ->where(
                        'id',
                        collect($args['tokenIds'])
                            ->map(fn ($tokenId) => $args['collectionId'] . '-' . $this->encodeTokenId(['tokenId' => $tokenId]))
                            ->all()
                    )
            )
            ->cursorPaginateWithTotalDesc('id', $args['first']);
    }

    /**
     * Get the validation rules.
     */
    #[Override]
    protected function rules(array $args = []): array
    {
        return [
            // If ids are present, collectionId and tokenIds should not be present
            'ids' => ['nullable', 'max:100', 'distinct'],
            // If collectionId is present, ids should not be present
            'collectionId' => ['nullable', 'required_with:tokenIds', new MinBigInt(0), new MaxBigInt(Hex::MAX_UINT128)],
            // If tokenIds is present, ids should not be present and collectionId should be present
            'tokenIds' => ['nullable', 'max:100', 'distinct'],
        ];
    }
}
