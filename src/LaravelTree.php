<?php

namespace Laraditz\LaravelTree;

use Illuminate\Database\Schema\Blueprint;

class LaravelTree
{
    const COLUMN_PARENT_ID = 'parent_id';

    const COLUMN_TREE_PATH = 'tree_path';

    const COLUMN_DEPTH = 'depth';

    const COLUMN_SORT_NO = 'sort_no';

    /**
     * Add default columns to the table. Also create an index.
     *
     * @param \Illuminate\Database\Schema\Blueprint $table
     */
    public static function addColumns(Blueprint $table)
    {
        $table->unsignedInteger(self::COLUMN_PARENT_ID)->nullable();
        $table->unsignedInteger(self::COLUMN_DEPTH)->nullable();
        $table->text(self::COLUMN_TREE_PATH)->nullable();
        $table->unsignedInteger(self::COLUMN_SORT_NO)->nullable();

        $table->index(self::COLUMN_PARENT_ID);
        $table->index(self::COLUMN_DEPTH);
        $table->index(self::COLUMN_SORT_NO);
    }

    /**
     * Drop columns.
     *
     * @param \Illuminate\Database\Schema\Blueprint $table
     */
    public static function dropColumns(Blueprint $table)
    {
        $columns = static::getAllColumns();

        $table->dropIndex(self::COLUMN_PARENT_ID);
        $table->dropIndex(self::COLUMN_DEPTH);
        $table->dropIndex(self::COLUMN_SORT_NO);
        $table->dropColumn($columns);
    }

    /**
     * Get a list of default columns.
     *
     * @return array
     */
    public static function getAllColumns()
    {
        return [static::COLUMN_PARENT_ID, static::COLUMN_TREE_PATH, static::COLUMN_DEPTH, static::COLUMN_SORT_NO];
    }
}
