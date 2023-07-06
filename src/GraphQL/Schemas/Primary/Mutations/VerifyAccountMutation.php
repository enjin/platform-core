<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\CryptoSignatureType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Interfaces\PlatformPublicGraphQlOperation;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Database\VerificationService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class VerifyAccountMutation extends Mutation implements PlatformGraphQlMutation, PlatformPublicGraphQlOperation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'VerifyAccount',
            'description' => __('enjin-platform::mutation.verify_account.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Boolean!');
    }

    /**
     * Get the mutation's arguments definition..
     */
    public function args(): array
    {
        return [
            'verificationId' => [
                'type' => GraphQL::type('String!'),
                'rules' => ['bail', 'filled', 'exists:verifications,verification_id'],
            ],
            'signature' => [
                'type' => GraphQL::type('String!'),
                'rules' => ['bail', 'filled', new ValidHex()],
            ],
            'account' => [
                'type' => GraphQL::type('String!'),
                'rules' => ['bail', 'filled', new ValidSubstrateAccount()],
            ],
            'cryptoSignatureType' => [
                'type' => GraphQL::type('CryptoSignatureType'),
                'description' => __('enjin-platform::query.verify_message.args.cryptoSignatureType'),
                'defaultValue' => CryptoSignatureType::ED25519->name,
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve(
        $root,
        array $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields,
        VerificationService $verificationService
    ): mixed {
        $key = "{$args['verificationId']}.{$args['account']}";
        $lock = Cache::lock(PlatformCache::VERIFY_ACCOUNT->key($key), 5);
        if (!$lock->get()) {
            throw new PlatformException(__('enjin-platform::error.unable_to_process'));
        }


        $response = false;

        try {
            $response = $verificationService->verify(
                $args['verificationId'],
                $args['signature'],
                $args['account'],
                $args['cryptoSignatureType'] ?? CryptoSignatureType::ED25519->name
            );
        } finally {
            $lock->release();
        }

        return $response;
    }
}
