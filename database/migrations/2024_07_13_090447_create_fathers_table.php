<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            if (DB::getDriverName() === 'sqlite') {
                $table->string('full_name')->storedAs("first_name || ' ' || last_name"); // SQLite concatenation
            } else {
                $table->string('full_name')->storedAs("CONCAT(first_name, ' ', last_name)"); // MySQL syntax
            }            $table->timestamps(); // created_at, updated_at => ignored because they are nullable
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_test_models');
    }
}
