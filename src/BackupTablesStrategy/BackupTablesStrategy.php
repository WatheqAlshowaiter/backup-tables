<?php

namespace WatheqAlshowaiter\BackupTables\BackupTablesStrategy;

abstract class BackupTablesStrategy
{
    abstract public function backup($newTableName, $table): array;
}
