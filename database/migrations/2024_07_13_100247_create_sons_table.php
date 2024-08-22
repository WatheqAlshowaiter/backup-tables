<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSonsTable extends Migration
{
    public function up(): void
    {
        Schema::create('sons', function (Blueprint $table) {
            $table->bigIncrements('id'); // primary key => ignored
            //$table->unsignedBigInteger('father_id');
            $table->foreignId('father_id'); // todo stop foreign keys temp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sons');
    }
}
