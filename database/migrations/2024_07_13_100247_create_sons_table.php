<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use WatheqAlshowaiter\BackupTablesServiceProvider\Constants;

class CreateSonsTable extends Migration
{
    public function up(): void
    {
        Schema::create('sons', function (Blueprint $table) {
            $table->bigIncrements('id'); // primary key => ignored
            $table->unsignedBigInteger('father_id');
            $table->foreign('father_id')->references('id')->on('fathers'); // required
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sons');
    }
}
