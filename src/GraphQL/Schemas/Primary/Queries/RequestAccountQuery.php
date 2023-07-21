<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Queries;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Services\Database\VerificationService;
use Enjin\Platform\Services\Database\WalletService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class RequestAccountQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySchema;

    /**
     * Get the query's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'RequestAccount',
            'description' => __('enjin-platform::query.request_account.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('AccountRequest!');
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
        VerificationService $verificationService,
        WalletService $walletService
    ): mixed {
        $data = $verificationService->generate();
        $verification = $verificationService->store($data);

        return [
            'qrCode' => $verificationService->qr(
                $verification->verification_id,
                $verification->code,
            ),
            'verificationId' => $verification->verification_id,
        ];
    }
}
