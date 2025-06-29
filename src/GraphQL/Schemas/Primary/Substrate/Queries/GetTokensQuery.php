<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Base\Query;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Token;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetTokensQuery extends Query implements PlatformGraphQlQuery
{
    use HasEncodableTokenId;
    use InPrimarySubstrateSchema;

    protected $middleware = [
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
            ],
            'collectionId' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::query.get_tokens.args.collectionId'),
                'rules' => ['nullable', 'required_with:tokenIds'],
            ],
            'tokenIds' => [
                'type' => GraphQL::type('[EncodableTokenIdInput]'),
                'description' => __('enjin-platform::query.get_tokens.args.tokenIds'),
                'rules' => ['nullable', 'array', 'min:0', 'max:100', 'distinct'],
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
            'after' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (!Arr::get(Cursor::fromEncoded($value)?->toArray(), 'identifier')) {
                        $fail('enjin-platform::validation.invalid_after')->translate();
                    }

                },
            ],
        ];
    }
}
