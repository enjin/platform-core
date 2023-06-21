<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Base\Query;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasTokenIdFields;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Rules\TokenEncodeExists;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetTokenQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;
    use HasTokenIdFields;
    use HasTokenIdFieldRules;
    use HasEncodableTokenId;

    /**
     * Get the query's attributes.
     */
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
        return GraphQL::type('Token!');
    }

    /**
     * Get the query's arguments definition.
     */
    public function args(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::query.get_token.args.collectionId'),
                'rules' => ['exists:Enjin\Platform\Models\Collection,collection_chain_id'],
            ],
            ...$this->getTokenFields(__('enjin-platform::query.get_token.args.tokenId')),
        ];
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        return Token::loadSelectFields($resolveInfo, $this->name)
            ->whereHas('collection', fn ($query) => $query->where('collection_chain_id', $args['collectionId']))
            ->where('token_chain_id', $this->encodeTokenId($args))
            ->first();
    }

    /**
     * Get the validatio rules.
     */
    protected function rules(array $args = []): array
    {
        return $this->getTokenFieldRules(
            null,
            [new TokenEncodeExists()]
        );
    }
}
