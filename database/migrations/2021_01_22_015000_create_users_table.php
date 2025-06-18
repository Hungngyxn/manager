<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
