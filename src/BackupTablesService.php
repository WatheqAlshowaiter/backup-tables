<?php

namespace WatheqAlshowaiter\BackupTables;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

class BackupTablesService
{
    public array $response = [];

    public function backupTables($tablesToBackup)
    {
        $tablesToBackup = Arr::wrap($tablesToBackup);

        if (empty($tablesToBackup)) {
            $this->response[] = 'No tables specified to clone.';

            return false;
        }

        $result = $this->processBackup($tablesToBackup);

        $output = new ConsoleOutput;

        foreach ($result['response'] as $message) {
            $output->writeln($message);
        }

        if (! empty($result['newCreatedTables'])) {
            $output->writeln('All tables cloned successfully ..');
            $output->writeln('Newly created tables:');
            foreach ($result['newCreatedTables'] as $tableName) {
                $output->writeln($tableName);
            }

            return true;
        }

        return false;
    }

    protected function processBackup(array $tablesToBackup = [])
    {
        $currentDateTime = now()->format('Y_m_d_H_i_s');

        foreach ($tablesToBackup as $table) {
            $newTableName = $table.'_backup_'.$currentDateTime;
            $newTableName = str_replace(['-', ':'], '_', $newTableName);

            if (Schema::hasTable($newTableName)) {
                $this->response[] = "Table '$newTableName' already exists. Skipping cloning for '$table'.";

                continue;
            }

            if (! Schema::hasTable($table)) {
                $this->response[] = "Table `$table` is not exists. check the table name again";

                continue;
            }

            $databaseDriver = DB::connection()->getDriverName();

            Schema::disableForeignKeyConstraints();

            switch ($databaseDriver) {
                case 'sqlite':
                    $this->backupTablesForSqlite($newTableName, $table);
                    break;
                case 'mysql':
                case 'mariadb':
                    $this->backupTablesForForMysqlAndMariaDb($newTableName, $table);
                    break;
                case 'pgsql':
                    $this->backupTablesForForPostgres($newTableName, $table);
                    break;
                case 'sqlsrv':
                    $this->backupTablesForForSqlServer($newTableName, $table);
                    break;
                default:
                    throw new \Exception('NOT SUPPORTED DATABASE DRIVER');
            }
            Schema::enableForeignKeyConstraints();

        }

        return [
            'response' => $this->response,
            //'newCreatedTables' =>$this->response['newCreatedTables'],
        ];

        function restoreTable($tableName, $backupName)
        {
            // todo
        }

    }

    protected function backupTablesForSqlite($newTableName, $table)
    {
        // Step 1: Create the new table structure, excluding generated columns
        DB::statement(/**@lang SQLite */ "CREATE TABLE $newTableName AS SELECT * FROM $table WHERE 1=0;");

        //$allColumns = DB::selectOne(/**@lang SQLite* */ "select * from $table");

        DB::statement(/**@lang SQLite */ "INSERT INTO $newTableName SELECT * FROM $table");

        $newCreatedTables[] = $newTableName;
        $response[] = " Table '$table' cloned successfully.";

        return [
            'response' => $response,
            'newCreatedTables' => $newCreatedTables,
        ];
    }

    protected function backupTablesForForMysqlAndMariaDb($newTableName, $table): array
    {
        logger('mariadb');

        DB::statement(/**@lang MySQL*/ "CREATE TABLE $newTableName AS SELECT * FROM $table");

        $newCreatedTables[] = $newTableName;
        $response[] = " Table '$table' cloned successfully.";

        return [
            'response' => $response,
            'newCreatedTables' => $newCreatedTables,
        ];
    }

    protected function backupTablesForForPostgres($newTableName, $table)
    {
        DB::statement(/**@lang PostgreSQL*/ "CREATE TABLE $newTableName AS SELECT * FROM $table");

        $newCreatedTables[] = $newTableName;
        $response[] = " Table '$table' cloned successfully.";

        return [
            'response' => $response,
            'newCreatedTables' => $newCreatedTables,
        ];
    }

    protected function backupTablesForForSqlServer($newTableName, $table)
    {
        DB::statement(/**@lang TSQL*/"SELECT * INTO $newTableName FROM $table");

        $newCreatedTables[] = $newTableName;
        $response[] = " Table '$table' cloned successfully.";

        return [
            'response' => $response,
            'newCreatedTables' => $newCreatedTables,
        ];
    }

    private function getMysqlVersion()
    {
        return (float) DB::select('select version()')[0]->{'version()'};
    }
}
