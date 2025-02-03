<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Carbon\Carbon;
use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Block;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Rules\TokenExistsInCollection;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Mail\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class TokenHolderSnapshotMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    public static $resolveUserEmail;
    public static $bypassRateLimiting = false;

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'TokenHolderSnapshot',
            'description' => __('enjin-platform::mutation.token_holder_snapshot.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('String!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.token_holder_snapshot.args.collectionId'),
            ],
            'tokenId' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::mutation.token_holder_snapshot.args.tokenId'),
            ],
            'blockOrTimestamp' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::mutation.token_holder_snapshot.args.blockOrTimestamp'),
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        if (!($email = $this->getUserEmail())) {
            return trans('enjin-platform::error.snapshot_email_not_configured');
        }

        if (!static::$bypassRateLimiting) {
            $key = 'TokenHolderSnapshotRequest' . $email;
            if (RateLimiter::tooManyAttempts($key, 1)) {
                return trans('enjin-platform::error.too_many_requests', ['num' => RateLimiter::availableIn($key)]);
            }
            RateLimiter::increment($key);
        }

        $timestamp = '';
        if ($blockOrTimestamp = Arr::get($args, 'blockOrTimestamp')) {
            $timestamp = strlen((string) $blockOrTimestamp) === 26
                ? Carbon::createFromTimestamp($timestamp)->toDateTimeString()
                : Block::where('number', $blockOrTimestamp)->value('created_at')?->toDateTimeString();
        }

        $tokens = Token::with(['accounts', 'accounts.wallet'])
            ->where(
                'collection_id',
                Collection::where('collection_chain_id', $collectionId = Arr::get($args, 'collectionId'))->value('id')
            )->when($tokenId = Arr::get($args, 'tokenId'), fn ($query) => $query->where('token_chain_id', $tokenId))
            ->when($timestamp, fn ($query) => $query->where('created_at', '<=', $timestamp))
            ->get(['id', 'token_chain_id', 'collection_id']);

        if (empty($tokens)) {
            $message = trans('enjin-platform::mutation.token_holder_snapshot.no_tokens_found');
            Log::info('TokenHolderSnapshot mutation request failed', [
                'email' => $email,
                'collection_id' => $collectionId,
                'tokenId' => $tokenId,
                'blockOrTimestamp' => $blockOrTimestamp,
                'message' => $message,
            ]);

            return $message;
        }

        $tokens->sortBy('collection_id')->sortByDesc('balance');

        $data = '';
        $tokenLength = $tokens->count() - 1;
        $tokens->each(function ($token, $index) use (&$data, $collectionId, $tokenLength): void {
            $data .= 'Token ID,' . $collectionId . '-' . $token->token_chain_id;
            $data .= "\nAddress, Balance";
            $token->accounts->each(function ($account) use (&$data): void {
                $data .= "\n" . $account->wallet->address . ',' . $account->balance;
            });
            if ($tokenLength != $index) {
                $data .= "\n,\n";
            }
        });

        $filename = 'token_holders_snapshot_' . $collectionId
            . ($tokenId ? ('_' . $tokenId) : '')
            . ($blockOrTimestamp ? ('_' . $blockOrTimestamp) : '')
            . '.csv';
        dispatch(function () use ($email, $data, $filename): void {
            Mail::raw(
                "Your request for a token holders snapshot has been processed successfully.\nPlease find the requested CSV file attached to this email.",
                fn (Message $message) => $message->to($email)
                    ->subject('Your Token Holders Snapshot is Ready')
                    ->attachData($data, $filename, ['mime' => 'text/csv'])
            );
        })->onQueue(config('enjin-platform.queue'));

        Log::info('TokenHolderSnapshot mutation request', [
            'email' => $email,
            'collection_id' => $collectionId,
            'tokenId' => $tokenId,
            'blockOrTimestamp' => $blockOrTimestamp,
            'message' => 'success',
        ]);

        return trans('enjin-platform::mutation.token_holder_snapshot.success');

    }

    protected function getUserEmail(): string
    {
        return (static::$resolveUserEmail
            ? call_user_func(static::$resolveUserEmail)
            : config('enjin-platform.token_holder_snapshot_email')
        ) ?? '';
    }

    #[\Override]
    protected function rules(array $args = []): array
    {
        return [
            'collectionId' => [
                'required',
                'exists:collections,collection_chain_id',
            ],
            'tokenId' => [
                'nullable',
                new TokenExistsInCollection(),
            ],
            'blockOrTimestamp' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}
