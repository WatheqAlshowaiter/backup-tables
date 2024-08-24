<?php

namespace WatheqAlshowaiter\BackupTables;

use Illuminate\Support\Facades\Facade;

/**
 * public function generateBackup($tablesToBackup, string $dataTimeText = 'Y_m_d_H_i_s'): bool
 * public function convertModelToTableName($table): string
 */
class BackupTables extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BackupTablesService::class;
    }
}
