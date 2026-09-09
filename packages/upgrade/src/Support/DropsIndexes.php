<?php

declare(strict_types=1);

namespace Lunar\Upgrade\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop a live index by its reported name so a later `dropColumn` can succeed.
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
     * @param  list<string>  $columns
     */
    protected function dropIndexIfExists(string $table, array $columns, string $type = 'index'): void
    {
        $wantUnique = $type === 'unique';

        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] !== $columns || (bool) $index['unique'] !== $wantUnique) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index, $wantUnique): void {
                if ($wantUnique) {
                    $blueprint->dropUnique($index['name']);

                    return;
                }

                $blueprint->dropIndex($index['name']);
            });

            return;
        }
    }
}
