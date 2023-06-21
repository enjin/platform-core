<?php

namespace Enjin\Platform\Support;

use Illuminate\Pagination\CursorPaginator;

class Paginator
{
    /**
     * Create an empty cursor page.
     */
    public static function emptyCursorPage(int $pageSize): array
    {
        return ['total' => 0, 'items' => new CursorPaginator([], $pageSize)];
    }
}
