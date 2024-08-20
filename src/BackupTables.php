<?php

namespace WatheqAlshowaiter\BackupTables;

use Illuminate\Support\Facades\Facade;

/**
 * public function backupTables($tablesToBackup = []): bool
 * protected function processBackup(array $tablesToBackup = []): array
 */
class BackupTables extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BackupTablesService::class;
    }
}
