<?php

namespace WatheqAlshowaiter\BackupTables\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use WatheqAlshowaiter\BackupTables\BackupTables;
use WatheqAlshowaiter\BackupTables\Constants;
use WatheqAlshowaiter\BackupTables\Tests\Models\Father;
use WatheqAlshowaiter\BackupTables\Tests\Models\Mother;
use WatheqAlshowaiter\BackupTables\Tests\Models\Son;

class BackupTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_when_table_is_not_correct()
    {
        $tableName = 'not_correct_table_name';

        $this->assertFalse(BackupTables::generateBackup($tableName));
    }

    public function test_return_when_table_string_empty()
    {
        $emptyString = '';
        $emptyArray = [];

        $this->assertFalse(BackupTables::generateBackup($emptyString));
        $this->assertFalse(BackupTables::generateBackup($emptyArray));
    }

    public function test_generate_single_table_backup()
    {
        $dateTime = Carbon::parse('2024-01-01 12:12:08');
        Carbon::setTestNow($dateTime);

        $tableName = 'fathers';
        BackupTables::generateBackup($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));

        $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));

        if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
            $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs/VirtualAs column
        }

        Carbon::setTestNow();
    }

    public function test_generate_single_table_backup_with_different_data()
    {
        $dateTime = Carbon::parse('2024-01-07 12:12:08');
        Carbon::setTestNow($dateTime);

        $tableName = 'mothers';

        Mother::create([
            'types' => 'one',
            'uuid' => Str::uuid(),
            'ulid' => '01J5Y93TVJRVFCSRQFHHF2NRC4',
            'description' => "{ar: 'some description'}",
        ]);

        BackupTables::generateBackup($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));

        $this->assertEquals(DB::table($tableName)->value('types'), DB::table($newTableName)->value('types'));
        $this->assertEquals(DB::table($tableName)->value('uuid'), DB::table($newTableName)->value('uuid'));
        $this->assertEquals(DB::table($tableName)->value('ulid'), DB::table($newTableName)->value('ulid'));
        $this->assertEquals(DB::table($tableName)->value('description'), DB::table($newTableName)->value('description'));

        Carbon::setTestNow();
    }

    public function test_generate_single_table_backup_then_another_table_backup_later()
    {
        $dateTime = Carbon::parse('2024-01-02 12:12:08');
        Carbon::setTestNow($dateTime);

        $fatherTable = 'fathers';
        $sonTable = 'sons';

        $father = Father::create([
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        Son::create([
            'father_id' => $father->id,
        ]);

        BackupTables::generateBackup($fatherTable);

        $currentDateTime = now()->format('Y_m_d_H_i_s');
        $newFatherTable = $fatherTable.'_backup_'.$currentDateTime;
        $newSonTable = $sonTable.'_backup_'.$currentDateTime;

        $this->assertTrue(Schema::hasTable($newFatherTable));

        $this->assertEquals(DB::table('fathers')->value('first_name'), DB::table($newFatherTable)->value('first_name'));
        $this->assertEquals(DB::table('fathers')->value('email'), DB::table($newFatherTable)->value('email'));

        BackupTables::generateBackup($sonTable);

        $this->assertTrue(Schema::hasTable($newSonTable));
        $this->assertEquals(DB::table('sons')->value('father_id'), DB::table($newSonTable)->value('father_id'));
        Carbon::setTestNow();
    }

    public function test_generate_multiple_table_backup()
    {
        $dateTime = Carbon::parse('2024-01-03 12:12:08');
        Carbon::setTestNow($dateTime);

        $tableName = 'fathers';
        $tableName2 = 'sons';

        Father::create([
            'id' => 1,
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        Son::create([
            'father_id' => Father::value('id'),
        ]);

        BackupTables::generateBackup([$tableName, $tableName2]);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');
        $newTableName2 = $tableName2.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));
        $this->assertTrue(Schema::hasTable($newTableName2));

        $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));

        if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
            $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs/VirtualAs column
        }

        $this->assertEquals(DB::table($tableName2)->value('father_id'), DB::table($newTableName2)->value('father_id'));

        Carbon::setTestNow();
    }

    public function test_generate_single_table_backup_with_with_custom_format()
    {
        $dateTime = Carbon::parse('2024-01-01 12:12:08');
        Carbon::setTestNow($dateTime);

        $tableName = 'fathers';
        $customFormat = 'Y_d_m_H_i';

        BackupTables::generateBackup($tableName, $customFormat);

        $newTableName = $tableName.'_backup_'.now()->format($customFormat);

        $this->assertTrue(Schema::hasTable($newTableName));
        Carbon::setTestNow();
    }

    public function test_generate_multiple_models_backup()
    {
        $dateTime = Carbon::parse('2024-01-04 12:12:08');
        Carbon::setTestNow($dateTime);
        $tableName = Father::class;
        $tableName2 = Son::class;

        Father::create([
            'id' => 1,
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        Son::create([
            'father_id' => Father::value('id'),
        ]);

        BackupTables::generateBackup([$tableName, $tableName2]);

        $tableName = BackupTables::convertModelToTableName($tableName);
        $tableName2 = BackupTables::convertModelToTableName($tableName2);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');
        $newTableName2 = $tableName2.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));
        $this->assertTrue(Schema::hasTable($newTableName2));

        $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));

        if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
            $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs/VirtualAs column
        }

        $this->assertEquals(DB::table($tableName2)->value('father_id'), DB::table($newTableName2)->value('father_id')); // foreign key
    }

    public function test_skip_duplicated_backups()
    {
        $dateTime = Carbon::parse('2024-01-05 12:12:08');
        Carbon::setTestNow($dateTime);

        $tableName = 'fathers';
        BackupTables::generateBackup($tableName);
        BackupTables::generateBackup($tableName); // another backup up will be skipped

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));

        $pattern = "{$newTableName}";
        $databaseDriver = DB::getDriverName();
        $count = 0;

        switch ($databaseDriver) {
            case 'mysql':
            case 'mariadb':
                $result = DB::select(/**@lang MySQL*/ '
                SELECT COUNT(*) as count
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_name LIKE ?', [$pattern]);
                $count = $result[0]->count;
                break;

            case 'pgsql':
                $result = DB::select(/**@lang PostgreSQL*/ "
                SELECT COUNT(*) as count
                FROM information_schema.tables
                WHERE table_schema = 'public'
                AND table_name LIKE ?", [$pattern]);
                $count = $result[0]->count;
                break;

            case 'sqlite':
                $count = DB::table('sqlite_master')
                    ->where('type', 'table')
                    ->where('name', 'like', $pattern)
                    ->count();
                break;

            case 'sqlsrv':
                $result = DB::select(/**@lang TSQL*/ '
                SELECT COUNT(*) as count
                FROM sys.tables
                WHERE name LIKE ?', [$pattern]);
                $count = $result[0]->count;
                break;
        }
        $this->assertEquals(1, $count);

        Carbon::setTestNow();
    }
}
