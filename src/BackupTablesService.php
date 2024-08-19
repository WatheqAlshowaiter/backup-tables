<?php

namespace WatheqAlshowaiter\BackupTablesServiceProvider;

use Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;
use function Exception;

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

        $output = new ConsoleOutput();

        foreach ($result['response'] as $message) {
            $output->writeln($message);
        }

        if (!empty($result['newCreatedTables'])) {
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
        //$response = [];
        //$newCreatedTables = [];

        $currentDateTime = now()->format('Y_m_d_H_i_s');

        foreach ($tablesToBackup as $table) {
            $newTableName = $table . '_backup_' . $currentDateTime;
            $newTableName = str_replace(['-', ':'], '_', $newTableName);

            if (Schema::hasTable($newTableName)) {
                $this->response[] = "Table '$newTableName' already exists. Skipping cloning for '$table'.";

                continue;
            }

            if (!Schema::hasTable($table)) {
                $this->response[] = "Table `$table` is not exists. check the table name again";
                continue;
            }


            $databaseDriver = DB::connection()->getDriverName();

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
                    return $this->backupTablesForForSqlServer($newTableName, $table);
                    break;
                default:
                    throw Exception('NOT SUPPORTED DATABASE DRIVER');
            }
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
        DB::statement(/**@lang SQLite* */ "CREATE TABLE $newTableName AS SELECT * FROM $table WHERE 1=0;");

        //$allColumns = DB::selectOne(/**@lang SQLite* */ "select * from $table");

        DB::statement(/**@lang SQLite* */ "INSERT INTO $newTableName SELECT * FROM $table");


        $newCreatedTables[] = $newTableName;
        $response[] = " Table '$table' cloned successfully.";


        return [
            'response' => $response,
            'newCreatedTables' => $newCreatedTables,
        ];
    }

    protected function backupTablesForForMysqlAndMariaDb($newTableName, $table): array
    {
        // Step 1: Create the new table structure, excluding generated columns
        DB::statement(/**@lang MySQL* */ "CREATE TABLE $newTableName AS SELECT * FROM $table WHERE 1=0;");

        // Step 2: Fetch all non-generated columns
        $nonGeneratedColumns = DB::table('INFORMATION_SCHEMA.COLUMNS')
            ->select('COLUMN_NAME')
            ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
            ->where('TABLE_NAME', $table)
            ->where('EXTRA', 'NOT LIKE', '%VIRTUAL GENERATED%')
            ->pluck('COLUMN_NAME')
            ->toArray();

        // Step 3: Escape reserved keywords and construct the column list
        $escapedColumns = array_map(function ($column) {
            return '`' . $column . '`'; // Escape column names with backticks
        }, $nonGeneratedColumns);

        // Convert array to comma-separated string
        $columnList = implode(', ', $escapedColumns);

        // Step 4: Insert data excluding generated columns
        DB::statement(/**@lang MySQL* */ "INSERT INTO $newTableName ($columnList) SELECT $columnList FROM $table");

        $newCreatedTables[] = $newTableName;
        $response[] = " Table '$table' cloned successfully.";

        return [
            'response' => $response,
            'newCreatedTables' => $newCreatedTables,
        ];
    }

    protected function backupTablesForForPostgres($newTableName, $table)
    {
        dd('postgres');

    }

    protected function backupTablesForForSqlServer($newTableName, $table)
    {
        dd('sql server');
    }
}
