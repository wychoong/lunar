<?php

declare(strict_types=1);

namespace Lunar\Upgrade\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop live indexes by their reported name so a later `dropColumn` can succeed.
 *
 * SQLite refuses `DROP COLUMN` while any index still references the column.
 * MariaDB errors 1072 (`Key column doesn't exist in table`). MySQL 8 and
 * Postgres often tolerate the drop, but dropping first is safe on every driver.
 *
 * `Schema::hasIndex(..., 'index')` misses SQLite, which reports the type as
 * `btree` rather than `index` — walk `Schema::getIndexes()` instead.
 */
trait DropsIndexes
{
    /**
     * Drop every non-primary index that includes any of the given columns.
     *
     * Use this immediately before `dropColumn`.
     *
     * @param  list<string>  $columns
     */
    protected function dropIndexesForColumns(string $table, array $columns): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['primary'] ?? false) === true) {
                continue;
            }

            if (array_intersect($index['columns'], $columns) === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                if ($index['unique']) {
                    $blueprint->dropUnique($index['name']);

                    return;
                }

                $blueprint->dropIndex($index['name']);
            });
        }
    }
}
