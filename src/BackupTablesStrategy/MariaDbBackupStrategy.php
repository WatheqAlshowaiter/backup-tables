<?php

namespace WatheqAlshowaiter\BackupTables\BackupTablesStrategy;


use Illuminate\Support\Facades\DB;
use WatheqAlshowaiter\BackupTables\Constants;

 class MariaDbBackupStrategy extends BackupTablesStrategy
 {
     public function backup($newTableName, $table): array
     {
         DB::statement(/**@lang MariaDB */ "CREATE TABLE $newTableName AS SELECT * FROM $table");

         return [
             'response' => "Table '$table' completed backup successfully.",
             'newCreatedTables' => "Newly created table: $newTableName",
         ];
     }
 }
