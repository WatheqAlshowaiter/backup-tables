<?php

namespace WatheqAlshowaiter\BackupTables;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

class BackupTablesService
{
    public array $response = [];

    /**
     * Generate backup for the given table or tables
     *
     * @param string|array $tablesToBackup
     * @param string $dataTimeText
     * @return bool
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

        if (! empty($result['newCreatedTables'])) {
            $output->writeln('All tables completed backup successfully..');
            $output->writeln('Newly created tables:');
            foreach ($result['newCreatedTables'] as $tableName) {
                $output->writeln($tableName);
            }

            return true;
        }

        return false;
    }

    protected function processBackup(array $tablesToBackup = [], $dateTimeFormat = 'Y_m_d_H_i_s'): array
    {
        $currentDateTime = now()->format($dateTimeFormat);

        foreach ($tablesToBackup as $table) {
            $table = $this->convertModelToTableName($table);

            $newTableName = $table . '_backup_' . $currentDateTime;
            $newTableName = str_replace(['-', ':'], '_', $newTableName);

            if (Schema::hasTable($newTableName)) {
                $this->response[] = "Table '$newTableName' already exists. Skipping backup for '$table'.";

                continue;
            }

            if (!Schema::hasTable($table)) {
                $this->response[] = "Table `$table` is not exists. check the table name again";

                continue;
            }

            $databaseDriver = DB::connection()->getDriverName();

            Schema::disableForeignKeyConstraints();

            switch ($databaseDriver) {
                case 'sqlite':
                    $this->response[] = $this->backupTablesForSqlite($newTableName, $table);
                    break;
                case 'mysql':
                    $this->backupTablesForForMysql($newTableName, $table);
                    break;
                case 'mariadb':
                    $this->backupTablesForForMariaDb($newTableName, $table);
                    break;
                case 'pgsql':
                    $this->backupTablesForForPostgres($newTableName, $table);
                    break;
                case 'sqlsrv':
                    $this->backupTablesForForSqlServer($newTableName, $table);
                    break;
                default:
                    throw new Exception('NOT SUPPORTED DATABASE DRIVER');
            }
            Schema::enableForeignKeyConstraints();
        }

        //return $this->response; // tested later


        return [
            'response' => $this->response,
            //'newCreatedTables' =>$this->response['newCreatedTables'],
        ];
    }

    protected function backupTablesForSqlite($newTableName, $table): array
    {
        DB::statement(/**@lang SQLite */ "CREATE TABLE $newTableName AS SELECT * FROM $table WHERE 1=0;");
        DB::statement(/**@lang SQLite */ "INSERT INTO $newTableName SELECT * FROM $table");

        return $this->returnedBackupResponse($newTableName, $table);
    }

    protected function backupTablesForForMysql($newTableName, $table): array
    {
        DB::statement(/**@lang MySQL*/ "CREATE TABLE $newTableName AS SELECT * FROM $table");

        return $this->returnedBackupResponse($newTableName, $table);
    }

    protected function backupTablesForForMariaDb($newTableName, $table): array
    {
        DB::statement(/**@lang MariaDB*/ "CREATE TABLE $newTableName AS SELECT * FROM $table");

        return $this->returnedBackupResponse($newTableName, $table);
    }

    protected function backupTablesForForPostgres($newTableName, $table): array
    {
        DB::statement(/**@lang PostgreSQL*/ "CREATE TABLE $newTableName AS SELECT * FROM $table");

        return $this->returnedBackupResponse($newTableName, $table);
    }

    protected function backupTablesForForSqlServer($newTableName, $table): array
    {
        DB::statement(/**@lang TSQL*/"SELECT * INTO $newTableName FROM $table");

        return $this->returnedBackupResponse($newTableName, $table);
    }

    /**
     * @param $table
     * @return string
     */
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
     * @param $newTableName
     * @param $table
     * @return array[]
     */
    public function returnedBackupResponse($newTableName, $table): array
    {
        $newCreatedTables[] = $newTableName;
        $response[] = " Table '$table' completed backup successfully.";

        return [
            'response' => $response,
            'newCreatedTables' => $newCreatedTables,
        ];
    }
}
