<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use WatheqAlshowaiter\BackupTables\Constants;

class CreateSonsTable extends Migration
{
    public function up(): void
    {
        Schema::create('sons', function (Blueprint $table) {
            $table->bigIncrements('id'); // primary key => ignored

            if((float) App::version() >= Constants::VERSION_AFTER_FOREIGN_ID_SUPPORT){
                $table->foreignId('father_id');
            }else {
                $table->unsignedBigInteger('father_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sons');
    }
}
