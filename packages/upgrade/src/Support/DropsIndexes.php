<?php

declare(strict_types=1);

namespace Lunar\Upgrade\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop a live index so a later `dropColumn` can succeed.
 *
 * SQLite refuses `DROP COLUMN` while any index still references the column.
 * MariaDB errors 1072 (`Key column doesn't exist in table`). MySQL 8 and
 * Postgres often tolerate the drop, but dropping first is safe on every driver.
 */
trait DropsIndexes
{
    /**
     * @param  list<string>  $columns
     */
    protected function dropIndexIfExists(string $table, array $columns): void
    {
        if (! Schema::hasIndex($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->dropIndex($columns);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    protected function dropUniqueIfExists(string $table, array $columns): void
    {
        if (! Schema::hasIndex($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->dropUnique($columns);
        });
    }
}
