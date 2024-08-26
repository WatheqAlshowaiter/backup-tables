<?php

namespace WatheqAlshowaiter\BackupTables\BackupTablesStrategy;

use Illuminate\Support\Facades\DB;

class PostgresBackupStrategy extends BackupTablesStrategy
{
    public function backup($newTableName, $table): array
    {
        DB::statement(/**@lang PostgreSQL */ "CREATE TABLE $newTableName AS SELECT * FROM $table");

        return [
            'response' => "Table '$table' completed backup successfully.",
            'newCreatedTables' => "Newly created table: $newTableName",
        ];
    }
}
