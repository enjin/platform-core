<?php

namespace Enjin\Platform\Traits;

use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;

trait HasFieldPagination
{
    /**
     * Resolves the current page for pagination.
     */
    protected function resolveCurrentPageForPagination(?string $cursor): void
    {
        CursorPaginator::currentCursorResolver(fn() => Cursor::fromEncoded($cursor ?? null));
    }
}
