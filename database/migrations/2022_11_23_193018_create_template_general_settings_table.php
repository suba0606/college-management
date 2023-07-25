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
        Schema::create('template_general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('display_name', 50);
            $table->string('key', 50);
            $table->text('value', 255);
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
        Schema::dropIfExists('template_general_settings');
    }
};
