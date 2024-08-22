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
            $table->bigIncrements('id'); // primary key -> ignored
            $table->boolean('active')->default(false); // default => ignored
            $table->string('first_name'); // required
            $table->string('last_name'); // required
            $table->string('email'); // required

            if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb') {
                $table->string('full_name')->virtualAs("CONCAT(first_name, ' ', last_name)");
                $table->string('status')->storedAs('IF(active = 1, TRUE, FALSE)');
            }

            if ((float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT && DB::getDriverName() == 'sqlite') {
                $table->string('full_name')->virtualAs("first_name || ' ' || last_name"); // (MySQL/PostgreSQL/SQLite)
                $table->string('status')->storedAs("CASE WHEN active = 1 THEN 'Active' ELSE 'Inactive' END"); // (MySQL/PostgreSQL/SQLite)
            }

            if ((float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT && DB::getDriverName() == 'pgsql') {
                $table->string('full_name')->storedAs("first_name || ' ' || last_name");
                $table->string('status')->storedAs("CASE WHEN active THEN 'Active' ELSE 'Inactive' END");
            }

            // todo SQL Server persisted()

            $table->timestamps(); // created_at, updated_at => ignored because they are nullable
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('fathers');
    }
}
