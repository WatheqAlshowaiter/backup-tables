<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSonsTable extends Migration
{
    public function up(): void
    {
        Schema::create('sons', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('father_id');
            $table->foreign('father_id')->references('id')->on('fathers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sons');
    }
}
