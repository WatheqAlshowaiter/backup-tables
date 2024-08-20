<?php

namespace WatheqAlshowaiter\BackupTables\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use WatheqAlshowaiter\BackupTables\BackupTables;
use WatheqAlshowaiter\BackupTables\Constants;
use WatheqAlshowaiter\BackupTables\Models\Father;
use WatheqAlshowaiter\BackupTables\Models\Son;
use Illuminate\Support\Facades\DB;

class BackupTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_when_table_is_not_correct()
    {
        $tableName = 'not_correct_table_name';

        $this->assertFalse(BackupTables::backupTables($tableName));
    }

    public function test_return_when_table_string_empty()
    {
        $emptyString = '';
        $emptyArray = [];

        $this->assertFalse(BackupTables::backupTables($emptyString));
        $this->assertFalse(BackupTables::backupTables($emptyArray));
    }

    public function test_generate_single_table_backup_with_proper_name()
    {
        Carbon::setTestNow();

        $tableName = 'fathers';
        BackupTables::backupTables($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));
    }

    public function test_generate_single_table_backup_all_table_data()
    {
        Carbon::setTestNow();
        $tableName = 'fathers';

        Father::create([
            'id' => 1,
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        BackupTables::backupTables($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));

        $this->assertEquals(Father::value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(Father::value('email'), DB::table($newTableName)->value('email'));

        if(DB::getDriverName() == 'mysql' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT){
            $this->assertEquals(Father::value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs tables
            $this->assertEquals(Father::value('status'), DB::table($newTableName)->value('status')); // virtualAs tables
        }
    }

    public function test_generate_multiple_table_backup()
    {
        Carbon::setTestNow();
        $tableName = 'fathers';
        $tableName2 = 'sons';

        Father::create([
            'id' => 1,
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        Son::create([
            'father_id' => 1,
        ]);

        BackupTables::backupTables([$tableName2, $tableName]);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');
        $newTableName2 = $tableName2.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));
        $this->assertTrue(Schema::hasTable($newTableName2));

        $this->assertEquals(Father::value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(Father::value('email'), DB::table($newTableName)->value('email'));

        if(DB::getDriverName() == 'mysql' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT){
            $this->assertEquals(Father::value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs tables
            $this->assertEquals(Father::value('status'), DB::table($newTableName)->value('status')); // virtualAs tables
        }

        $this->assertEquals(Son::value('father_id'), DB::table($newTableName2)->value('father_id')); // foreign key
    }

    public function test_generate_shallow_table_backup()
    {
        $this->markTestSkipped();
    }

    public function test_generate_only_data_table_backup()
    {
        $this->markTestSkipped();
    }

    public function test_generate_multiple_models_backup()
    {
        $this->markTestSkipped();
    }
}
