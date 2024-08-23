<?php

namespace WatheqAlshowaiter\BackupTables\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use WatheqAlshowaiter\BackupTables\BackupTables;
use WatheqAlshowaiter\BackupTables\Constants;
use WatheqAlshowaiter\BackupTables\Models\Father;
use WatheqAlshowaiter\BackupTables\Models\Mother;
use WatheqAlshowaiter\BackupTables\Models\Son;

class BackupTablesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

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

    public function test_generate_single_table_backup_with_proper_name()
    {
        Carbon::setTestNow();

        $tableName = 'fathers';
        BackupTables::generateBackup($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));
    }

    public function test_generate_single_table_backup_with_with_custom_format()
    {
        Carbon::setTestNow();
        $tableName = 'fathers';
        $customFormat = 'Y_d_m_H_i';

        BackupTables::generateBackup($tableName, $customFormat);

        $newTableName = $tableName.'_backup_'.now()->format($customFormat);

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

        BackupTables::generateBackup($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));

        if (DB::getDriverName() == 'mysql') { // todo debugging
            dump(Father::first()->first_name);
        }

        $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));

        if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
            $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs tables
            //$this->assertEquals(DB::table($tableName)->value('status'), DB::table($newTableName)->value('status')); // virtualAs tables
        }
    }

    public function test_generate_single_table_backup_with_different_data()
    {
        Carbon::setTestNow();
        $tableName = 'mothers';

        Mother::create([
            'types' => 'one',
            'uuid' => Str::uuid(),
            'ulid' => '01J5Y93TVJRVFCSRQFHHF2NRC4',
            'description' => "{ar: 'some description'}"
        ]);

        BackupTables::generateBackup($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));

        $this->assertEquals(DB::table($tableName)->value('types'), DB::table($newTableName)->value('types'));
        $this->assertEquals(DB::table($tableName)->value('uuid'), DB::table($newTableName)->value('uuid'));
        $this->assertEquals(DB::table($tableName)->value('ulid'), DB::table($newTableName)->value('ulid'));
        $this->assertEquals(DB::table($tableName)->value('description'), DB::table($newTableName)->value('description'));

    }

    public function test_generate_2_single_table_backup_all_table_data()
    {
        Carbon::setTestNow();
        $table1 = 'fathers';
        $table2 = 'sons';

        Father::create([
            'id' => 1,
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        Son::create([
            'father_id' => 1,
        ]);

        BackupTables::generateBackup($table1);

        $currentDateTime = now()->format('Y_m_d_H_i_s');
        $newTableName =  $table1 . '_backup_' . $currentDateTime;
        $newTableName2 = $table2 . '_backup_' . $currentDateTime;

        $this->assertTrue(Schema::hasTable($newTableName));

        // Verify data in fathers_backup table
        $this->assertEquals(DB::table('fathers')->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table('fathers')->value('email'), DB::table($newTableName)->value('email'));

        // Verify data in sons_backup table
        BackupTables::generateBackup($table2);
        $this->assertTrue(Schema::hasTable($newTableName2));
        $this->assertEquals(DB::table('sons')->value('father_id'), DB::table($newTableName2)->value('father_id'));
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
            'father_id' => Father::value('id')
        ]);

        BackupTables::generateBackup([$tableName, $tableName2]);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');
        $newTableName2 = $tableName2.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));
        $this->assertTrue(Schema::hasTable($newTableName2));

        if (DB::getDriverName() == 'mysql') { // todo debugging
            dump(Father::first()->first_name);
        }

        $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));

        if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
            $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs tables
            //$this->assertEquals(DB::table($tableName)->value('status'), DB::table($newTableName)->value('status')); // todo remove if not needed
        }

        $this->assertEquals(DB::table($tableName2)->value('father_id'), DB::table($newTableName2)->value('father_id')); // foreign key
    }

    public function test_generate_multiple_models_backup()
    {
        Carbon::setTestNow();
        $tableName = Father::class;
        $tableName2 = Son::class;

        Father::create([
            'id' => 1,
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        Son::create([
            'father_id' => Father::value('id')
        ]);

        BackupTables::generateBackup([$tableName, $tableName2]);

        $modelParent = "Illuminate\Database\Eloquent\Model";

        $tableName = BackupTables::convertModelToTableName($tableName); //  $this->convertModelToTableName($tableName, $modelParent);
        $tableName2 = $this->convertModelToTableName($tableName2, $modelParent);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');
        $newTableName2 = $tableName2.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));
        $this->assertTrue(Schema::hasTable($newTableName2));

        if (DB::getDriverName() == 'mysql') { // todo debugging
            dump(Father::first()->first_name);
        }

        $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));

        if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
            $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs tables
            //$this->assertEquals(DB::table($tableName)->value('status'), DB::table($newTableName)->value('status')); // todo remove if not needed
        }

        $this->assertEquals(DB::table($tableName2)->value('father_id'), DB::table($newTableName2)->value('father_id')); // foreign key
    }

    public function convertModelToTableName($table, string $modelParent)
    {
        if (class_exists($table)) {
            if (is_subclass_of($table, $modelParent)) {
                $table = (new $table)->getTable();
            }
        }
        return $table;
    }
}
