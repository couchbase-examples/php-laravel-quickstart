<?php

namespace App\Models\Concerns;

use Couchbase\QueryOptions;
use Illuminate\Support\Collection;

/**
 * Single seam for SQL++ queries against the travel-sample `inventory` scope.
 *
 * Every request value is bound as a named parameter (`$name` in the
 * statement), so callers never splice user input into query text.
 * Query failures are not caught here: they propagate to the controller,
 * which reports them as a 500 instead of an empty (404) result.
 */
trait QueriesInventory
{
    /**
     * @param  string  $statement  SQL++ statement using `$name` placeholders
     * @param  array<string, mixed>  $parameters  placeholder name => value
     */
    protected static function queryInventory(string $statement, array $parameters = []): Collection
    {
        $options = new QueryOptions();
        if ($parameters) {
            $options->namedParameters($parameters);
        }

        $result = app('couchbase.bucket')->scope('inventory')->query($statement, $options);

        return collect($result->rows());
    }

    /**
     * Normalise request paging values to the non-negative integers SQL++ LIMIT/OFFSET require.
     *
     * @return array{limit: int, offset: int}
     */
    protected static function pagingParameters($offset, $limit): array
    {
        return ['limit' => max(0, (int) $limit), 'offset' => max(0, (int) $offset)];
    }
}
