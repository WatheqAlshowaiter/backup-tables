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

        //dump($result);
        $output = new ConsoleOutput;

        foreach ($result['response'] as $message) {
            $output->writeln($message);
        }

        if(! empty(data_get($result, 'response.0.newCreatedTables'))){
            return true;
        }

        //if (! empty($result ['response'][0]['newCreatedTables'])) {
        //    $output->writeln('All tables completed backup successfully..');
        //    $output->writeln('Newly created tables:');
        //    foreach ($result['response'][0]['newCreatedTables'] as $tableName) {
        //        $output->writeln($tableName);
        //    }
        //
        //    return true;
        //}

        return false;
    }

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
                    $this->response[] = $this->backupTablesForForMysql($newTableName, $table);
                    break;
                case 'mariadb':
                    $this->response[] = $this->backupTablesForForMariaDb($newTableName, $table);
                    break;
                case 'pgsql':
                    $this->response[] = $this->backupTablesForForPostgres($newTableName, $table);
                    break;
                case 'sqlsrv':
                    $this->response[] = $this->backupTablesForForSqlServer($newTableName, $table);
                    break;
                default:
                    throw new Exception('NOT SUPPORTED DATABASE DRIVER');
            }
            Schema::enableForeignKeyConstraints();
        }

        return [
            'response' => $this->response,
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
        //dd($this->response);
        //Arr::forget('');
        //if($this->response[0])
        $result =  [
            'response' => "Table '$table' completed backup successfully.",
            'newCreatedTables' => "Newly created table: $newTableName",
        ];

        Arr::forget($this->response, '0');

        return $result;
    }

    /**
     * @param string $table
     * @param string $currentDateTime
     * @return array|string|string[]
     */
    private function buildBackupFilename(string $table, string $currentDateTime)
    {
        $newTableName = $table . '_backup_' . $currentDateTime;
        return str_replace(['-', ':'], '_', $newTableName);
    }
}
