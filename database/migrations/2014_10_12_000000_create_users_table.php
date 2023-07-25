<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50)->nullable()->default(NULL);
            $table->string('last_name', 50)->nullable()->default(NULL);
            $table->string('name');
            $table->integer('role',);
            $table->string('email')->unique();
            $table->string('mobile', 20)->nullable()->default(NULL);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('active_tokan');
            $table->rememberToken();
            $table->string('profile_image')->nullable()->default(NULL);
            $table->integer('created_by',);
            $table->date('created_date')->nullable();
            $table->integer('status',)->default('1');
            $table->enum('trash', ['NO', 'YES'])->default('NO');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
