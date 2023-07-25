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
        Schema::create('template_left_menu', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('link', 255);
            $table->string('icon', 50);
            $table->integer('parent_id');
            $table->integer('is_parent');
            $table->enum('is_module', [0, 1]);
            $table->string('sort_order', 50);   
            $table->integer('status',)->default('1');
            $table->enum('trash', ['NO', 'YES'])->default('NO');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_left_menu');
    }
};
