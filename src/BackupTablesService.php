<?php

namespace WatheqAlshowaiter\BackupTables;

use Exception;
use Illuminate\Database\Eloquent\Model;
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
     * @return bool
     * @throws Exception
     */
    public function generateBackup($tablesToBackup)
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
        $modelParent = "Illuminate\Database\Eloquent\Model";

        foreach ($tablesToBackup as $table) {

            //if ($table instanceof \Illuminate\Database\Eloquent\Model) {
            //    $table = $table->getTable();
            //}
            $table = $this->convertModelToTableName($table, $modelParent);

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

            Schema::disableForeignKeyConstraints();

            switch ($databaseDriver) {
                case 'sqlite':
                    $this->backupTablesForSqlite($newTableName, $table);
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

        return [
            'response' => $this->response,
            //'newCreatedTables' =>$this->response['newCreatedTables'],
        ];
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

    protected function backupTablesForForMysql($newTableName, $table)
    {
        DB::statement(/**@lang MySQL*/ "CREATE TABLE $newTableName AS SELECT * FROM $table");

        $newCreatedTables[] = $newTableName;
        $response[] = " Table '$table' cloned successfully.";

        return [
            'response' => $response,
            'newCreatedTables' => $newCreatedTables,
        ];
    }

    protected function backupTablesForForMariaDb($newTableName, $table)
    {
        DB::statement(/**@lang MariaDB*/ "CREATE TABLE $newTableName AS SELECT * FROM $table");

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

    /**
     * @param $table
     * @param string $modelParent
     * @return mixed|string
     */
    public function convertModelToTableName($table, string $modelParent)
    {
        if (class_exists($table)) {
            if (is_subclass_of($table, $modelParent)) {
                $table = (new $table)->getTable();
            }
        }
        return $table;
    }

    private function getMysqlVersion()
    {
        return (float) DB::select('select version()')[0]->{'version()'};
    }
    function hasParents($object) {
        return (bool)class_parents($object);
    }

}
