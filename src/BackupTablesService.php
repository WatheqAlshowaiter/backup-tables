<?php

namespace WatheqAlshowaiter\BackupTables;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;
use WatheqAlshowaiter\BackupTables\BackupTablesStrategy\SqliteBackupStrategy;
use WatheqAlshowaiter\BackupTables\BackupTablesStrategy\MysqlBackupStrategy;
use WatheqAlshowaiter\BackupTables\BackupTablesStrategy\MariaDbBackupStrategy;
use WatheqAlshowaiter\BackupTables\BackupTablesStrategy\SqlServerBackupStrategy;
use WatheqAlshowaiter\BackupTables\BackupTablesStrategy\PostgresBackupStrategy;

class BackupTablesService
{
    public array $response = [];

    /**
     * Generate backup for the given table or tables
     *
     * @param  string|array  $tablesToBackup
     *
     * @throws Exception
     */
    public function generateBackup($tablesToBackup, string $dataTimeText = 'Y_m_d_H_i_s'): bool
    {
        $tablesToBackup = Arr::wrap($tablesToBackup);

        if (empty($tablesToBackup)) {
            $this->response[] = 'No tables specified to backup.';

            return false;
        }

        $result = $this->processBackup($tablesToBackup, $dataTimeText);

        $output = new ConsoleOutput;

        foreach ($result['response'] as $message) {
            $output->writeln($message);
        }

        if (! empty(data_get($result, 'response.0.newCreatedTables'))) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected function processBackup(array $tablesToBackup = [], $dateTimeFormat = 'Y_m_d_H_i_s'): array
    {
        $currentDateTime = now()->format($dateTimeFormat);

        foreach ($tablesToBackup as $table) {
            $table = $this->convertModelToTableName($table);

            $newTableName = $this->buildBackupFilename($table, $currentDateTime);

            if (Schema::hasTable($newTableName)) {
                $this->response[] = "Table '$newTableName' already exists. Skipping backup for '$table'.";

                continue;
            }

            if (! Schema::hasTable($table)) {
                $this->response[] = "Table `$table` is not exists. check the table name again";

                continue;
            }

            $databaseDriver = DB::connection()->getDriverName();

            $backupStrategy = $this->getBackupStrategy($databaseDriver);

            Schema::disableForeignKeyConstraints();

            $this->response[] = $backupStrategy->backup($newTableName, $table);

            Schema::enableForeignKeyConstraints();
        }

        return [
            'response' => $this->response,
        ];
    }

    public function convertModelToTableName($table): string
    {
        $modelParent = "Illuminate\Database\Eloquent\Model";
        if (class_exists($table)) {
            if (is_subclass_of($table, $modelParent)) {
                $table = (new $table)->getTable();
            }
        }

        return $table;
    }

    /**
     * @return array[]
     */
    protected function returnedBackupResponse($newTableName, $table): array
    {
        $result = [
            'response' => "Table '$table' completed backup successfully.",
            'newCreatedTables' => "Newly created table: $newTableName",
        ];

        // to prevent duplicating message if you use generateBackup() twice in the same request event for different tables
        Arr::forget($this->response, '0');

        return $result;
    }

    /**
     * @return array|string|string[]
     */
    protected function buildBackupFilename(string $table, string $currentDateTime)
    {
        $newTableName = $table.'_backup_'.$currentDateTime;

        return str_replace(['-', ':'], '_', $newTableName);
    }

    /**
     * @throws Exception
     */
    protected function getBackupStrategy(string $databaseDriver)
    {
        switch ($databaseDriver) {
            case 'sqlite':
                return new SqliteBackupStrategy();
            case 'mysql':
                return new MysqlBackupStrategy();
            case 'mariadb':
                return new MariaDbBackupStrategy();
            case 'pgsql':
                return new PostgresBackupStrategy();
            case 'sqlsrv':
                return new SqlServerBackupStrategy();
            default:
                throw new Exception('NOT SUPPORTED DATABASE DRIVER');
        }
    }
}
