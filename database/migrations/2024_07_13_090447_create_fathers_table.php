<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use WatheqAlshowaiter\BackupTables\Constants;

class CreateFathersTable extends Migration
{
    public function up(): void
    {
        Schema::create('fathers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('active')->default(false);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');

            if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb') {
                $table->string('full_name')->virtualAs("CONCAT(first_name, ' ', last_name)");
            }

            if ((float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT && DB::getDriverName() == 'sqlite') {
                $table->string('full_name')->virtualAs("first_name || ' ' || last_name");
            }

            if ((float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT && DB::getDriverName() == 'pgsql') {
                $table->string('full_name')->storedAs("first_name || ' ' || last_name");
            }


            $table->timestamps();
        });

        if ((float)App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT && DB::getDriverName() == 'sqlsrv') {
            DB::statement(/**@lang TSQL */ "
            ALTER TABLE fathers
            ADD full_name AS CONCAT(first_name, ' ', last_name) PERSISTED;
        ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fathers');
    }
}
