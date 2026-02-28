<?php

namespace App\Database\Concerns;

use Illuminate\Support\Facades\Schema;

trait HasIndexHelpers
{
    protected function indexExists(string $table, string $indexName): bool
    {
        return in_array($indexName, array_column(Schema::getIndexes($table), 'name'), true);
    }
}
