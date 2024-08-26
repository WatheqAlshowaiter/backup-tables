<?php

namespace WatheqAlshowaiter\BackupTables\BackupTablesStrategy;

use Illuminate\Support\Facades\DB;

class SqliteBackupStrategy extends BackupTablesStrategy
{
    public function backup($newTableName, $table): array
    {
        DB::statement(/**@lang SQLite */ "CREATE TABLE $newTableName AS SELECT * FROM $table WHERE 1=0;");
        DB::statement(/**@lang SQLite */ "INSERT INTO $newTableName SELECT * FROM $table");

        return [
            'response' => "Table '$table' completed backup successfully.",
            'newCreatedTables' => "Newly created table: $newTableName",
        ];
    }
}
