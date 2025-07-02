<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Base\Query;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasAdhocRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasTokenIdFields;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetTokenQuery extends Query implements PlatformGraphQlQuery
{
    use HasAdhocRules;
    use HasEncodableTokenId;
    use HasTokenIdFieldRules;
    use HasTokenIdFields;
    use InPrimarySubstrateSchema;

    /**
     * Get the query's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetToken',
            'description' => __('enjin-platform::query.get_token.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Token');
    }

    /**
     * Get the query's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('String'),
                'description' => '',
            ],
            'collectionId' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::query.get_token.args.collectionId'),
            ],
            ...$this->getTokenFields(__('enjin-platform::query.get_token.args.tokenId'), true),
        ];
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return Token::selectFields($getSelectFields)
            ->where('id', $args['id'] ?? "{$args['collectionId']}-{$this->encodeTokenId($args)}")
            ->first();
    }

    /**
     * Get the validation rules.
     */
    #[Override]
    protected function rules(array $args = []): array
    {
        return [
            'id' => ['nullable', 'required_without_all:collectionId,tokenId'],
            'collectionId' => ['nullable', 'required_without:id', 'present_with:tokenId', new MinBigInt(0), new MaxBigInt(Hex::MAX_UINT128)],
            ...$this->getOptionalTokenFieldRules(null, ['required_without:id', 'present_with:collectionId']),
        ];
    }
}
