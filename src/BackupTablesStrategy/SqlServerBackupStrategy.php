<?php

namespace WatheqAlshowaiter\BackupTables\BackupTablesStrategy;

use Illuminate\Support\Facades\DB;

class SqlServerBackupStrategy extends BackupTablesStrategy
{
    public function backup($newTableName, $table): array
    {
        DB::statement(/**@lang TSQL*/ "SELECT * INTO $newTableName FROM $table");

        return [
            'response' => "Table '$table' completed backup successfully.",
            'newCreatedTables' => "Newly created table: $newTableName",
        ];
    }
}
