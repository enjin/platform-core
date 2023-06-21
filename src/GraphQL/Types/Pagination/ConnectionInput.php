<?php

declare(strict_types=1);

namespace Enjin\Platform\GraphQL\Types\Pagination;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ConnectionInput
{
    /**
     * The common arguments for pagination.
     */
    public static function args(?array $args = null): array
    {
        return array_merge($args ?? [], [
            'after' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::connection_input.args.after'),
                'rules' => ['max:255'],
                'defaultValue' => null,
            ],
            'first' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::connection_input.args.first'),
                'rules' => ['nullable', 'integer', 'min:1', 'max:500'],
                'defaultValue' => config('enjin-platform.pagination.limit'),
            ],
        ]);
    }
}
