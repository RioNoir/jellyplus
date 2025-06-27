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
        Schema::create('streams', function (Blueprint $table) {
            $table->increments('stream_id');
            $table->string('stream_md5')->nullable();
            $table->string('stream_protocol')->nullable();
            $table->string('stream_container')->nullable();
            $table->string('stream_addon_id')->nullable();
            $table->string('stream_jellyfin_id')->nullable();
            $table->string('stream_imdb_id')->nullable();
            $table->string('stream_title')->nullable();
            $table->string('stream_url')->nullable();
            $table->string('stream_host')->nullable();
            $table->string('stream_server_id')->nullable();
            $table->timestamp('stream_watched_at')->nullable();
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
        Schema::dropIfExists('streams');
    }
};
