<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\Enums\Substrate\CryptoSignatureType;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class VerifyMessageQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    /**
     * Get the query's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'VerifyMessage',
            'description' => __('enjin-platform::query.verify_message.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Boolean!');
    }

    /**
     * Get the query's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return [
            'message' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::query.verify_message.args.message'),
                'rules' => ['bail', 'filled', new ValidHex()],
            ],
            'signature' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::query.verify_message.args.signature'),
                'rules' => ['bail', 'filled', new ValidHex()],
            ],
            'publicKey' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::query.verify_message.args.publicKey'),
                'rules' => ['bail', 'filled', new ValidHex(32)],
            ],
            'cryptoSignatureType' => [
                'type' => GraphQL::type('CryptoSignatureType'),
                'description' => __('enjin-platform::query.verify_message.args.cryptoSignatureType'),
                'defaultValue' => CryptoSignatureType::SR25519->name,
            ],
        ];
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, BlockchainServiceInterface $blockchainService): mixed
    {
        return $blockchainService->verifyMessage($args['message'], $args['signature'], $args['publicKey'], $args['cryptoSignatureType'] ?? CryptoSignatureType::SR25519->name);
    }
}
