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
        Schema::create('template_user_page_visit', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('page_url', 255);
            $table->string('request_type', 50);
            $table->string('user_ip', 50);
            $table->text('user_agent');
            $table->longText('params');
            $table->enum('is_ajax', ['NO', 'YES'])->default('NO');
            $table->string('user_event', 255);
            $table->text('custom_msg');
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
        Schema::dropIfExists('template_user_page_visit');
    }
};
