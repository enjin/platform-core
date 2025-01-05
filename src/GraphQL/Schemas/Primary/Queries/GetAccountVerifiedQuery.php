<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Queries;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Verification;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Rules\ValidVerificationId;
use Enjin\Platform\Services\Database\VerificationService;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetAccountVerifiedQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySchema;

    /**
     * Get the query's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetAccountVerified',
            'description' => __('enjin-platform::query.get_account_verified.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('AccountVerified!');
    }

    /**
     * Get the query's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return [
            'verificationId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_account_verified.args.verificationId'),
                'rules' => ['bail', 'required_without:account', 'prohibits:account', new ValidVerificationId()],
            ],
            'account' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_account_verified.args.account'),
                'rules' => [
                    'bail', 'required_without:verificationId', 'prohibits:verificationId', new ValidSubstrateAccount(),
                ],
            ],
        ];
    }

    /**
     * Resolve the query's request.
     */
    public function resolve(
        $root,
        array $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields,
        VerificationService $verificationService
    ): mixed {
        $verification = Verification::query()
            ->when($args['verificationId'] ?? false, fn (Builder $query) => $query->where('verification_id', '=', $args['verificationId']))
            ->when($args['account'] ?? false, fn (Builder $query) => $query->where('public_key', '=', SS58Address::getPublicKey($args['account'])))
            ->first();

        return [
            'account' => [
                'publicKey' => $publicKey = $verification?->public_key,
                'address' => $publicKey !== null ? SS58Address::encode($publicKey) : null,
            ],
            'verified' => $publicKey !== null,
        ];
    }
}
