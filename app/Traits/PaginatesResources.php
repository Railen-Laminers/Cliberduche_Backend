<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

trait PaginatesResources
{
    /**
     * Paginate a query using request parameters while enforcing sensible limits.
     *
     * Supports the following query parameters:
     * - page
     * - per_page
     */
    protected function paginateResource(
        Builder|Relation|LengthAwarePaginator $query,
        Request $request,
        int $defaultPerPage = 20,
        int $maxPerPage = 100
    ): LengthAwarePaginator {
        if ($query instanceof LengthAwarePaginator) {
            return $query;
        }

        $perPage = (int) $request->query('per_page', $defaultPerPage);

        if ($perPage <= 0) {
            $perPage = $defaultPerPage;
        }

        $perPage = min($perPage, $maxPerPage);

        return $query
            ->paginate($perPage)
            ->appends($request->only(['page', 'per_page']));
    }
}

